<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\TelegramConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class TelegramService
{
    // ─── Core helpers ────────────────────────────────────────────────────────

    private function token(): string
    {
        return config('services.telegram.bot_token', '');
    }

    /**
     * Send a text message to a specific chat ID using the global bot token.
     */
    private function send(string $chatId, string $msg): void
    {
        if (empty($chatId)) return;
        try {
            $client = Http::timeout(10);
            $proxy  = config('services.telegram.proxy');
            if ($proxy) {
                $client = $client->withOptions(['proxy' => $proxy]);
            }
            $response = $client->post("https://api.telegram.org/bot{$this->token()}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $msg,
                'parse_mode' => 'Markdown',
            ]);
            if (!$response->successful()) {
                Log::warning('Telegram API error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Telegram failed: ' . $e->getMessage());
        }
    }

    /**
     * Send a PDF document to a specific chat ID using the global bot token.
     */
    private function sendDocument(string $chatId, string $filename, string $pdfContent, string $caption): void
    {
        if (empty($chatId)) return;
        try {
            $client = Http::timeout(30);
            $proxy  = config('services.telegram.proxy');
            if ($proxy) {
                $client = $client->withOptions(['proxy' => $proxy]);
            }
            $response = $client->attach('document', $pdfContent, $filename, ['Content-Type' => 'application/pdf'])
                ->post("https://api.telegram.org/bot{$this->token()}/sendDocument", [
                    'chat_id'    => $chatId,
                    'caption'    => $caption,
                    'parse_mode' => 'Markdown',
                ]);
            if (!$response->successful()) {
                Log::warning('Telegram sendDocument error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Telegram sendDocument failed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast a text alert to the "document" group of all active branches.
     * Used for delete / print / import alerts.
     */
    private function broadcast(string $msg): void
    {
        TelegramConfig::where('is_active', true)
            ->whereNotNull('document_chat_id')
            ->where('document_chat_id', '!=', '')
            ->get()
            ->each(fn ($c) => $this->send($c->document_chat_id, $msg));
    }

    /**
     * Broadcast a PDF document to the "document" group of all active branches.
     * Used for receipt / invoice created, updated, downloaded alerts.
     */
    private function broadcastDocument(string $filename, string $pdfContent, string $caption): void
    {
        TelegramConfig::where('is_active', true)
            ->whereNotNull('document_chat_id')
            ->where('document_chat_id', '!=', '')
            ->get()
            ->each(fn ($c) => $this->sendDocument($c->document_chat_id, $filename, $pdfContent, $caption));
    }

    /**
     * Broadcast a PDF report to the report-type group of all active branches.
     * $groupField is one of: 'daily_chat_id', 'weekly_chat_id', 'monthly_chat_id'
     */
    private function broadcastReport(string $groupField, string $filename, string $pdfContent, string $caption): void
    {
        TelegramConfig::where('is_active', true)
            ->whereNotNull($groupField)
            ->where($groupField, '!=', '')
            ->get()
            ->each(fn ($c) => $this->sendDocument($c->$groupField, $filename, $pdfContent, $caption));
    }

    // ─── Scheduled PDF Reports (per-branch) ──────────────────────────────────

    /**
     * Build and send a financial report PDF to one specific branch config.
     */
    private function buildAndSendReport(
        Carbon $start,
        Carbon $end,
        string $label,
        TelegramConfig $config,
        string $chatIdField
    ): void {
        $chatId = $config->$chatIdField;
        if (empty($chatId)) return;

        $receipts = Receipt::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $invoices = Invoice::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $receiptUsd = $receipts->where('currency', 'USD')->sum('total_amount');
        $receiptKhr = $receipts->where('currency', 'KHR')->sum('total_amount');
        $invoiceUsd = $invoices->where('currency', 'USD')->sum('grand_total');
        $invoiceKhr = $invoices->where('currency', 'KHR')->sum('grand_total');
        $vatTotal   = $invoices->sum('vat_amount');

        $pdf      = $this->generateReportPdf($receipts, $invoices, $start, $end);
        $filename = strtolower($label) . '-report-' . $start->format('Y-m-d') . '.pdf';

        $receiptLine = "🧾 Receipts: {$receipts->count()}";
        if ($receiptUsd > 0) $receiptLine .= " | USD " . number_format($receiptUsd, 2);
        if ($receiptKhr > 0) $receiptLine .= " | KHR " . number_format($receiptKhr, 0);

        $invoiceLine = "📋 Invoices: {$invoices->count()}";
        if ($invoiceUsd > 0) $invoiceLine .= " | USD " . number_format($invoiceUsd, 2);
        if ($invoiceKhr > 0) $invoiceLine .= " | KHR " . number_format($invoiceKhr, 0);

        $caption = "📊 *{$label} Financial Report*\n"
                 . "🏢 Branch: {$config->name}\n"
                 . "📅 Period: {$start->format('d/m/Y')} → {$end->format('d/m/Y')}\n"
                 . "{$receiptLine}\n"
                 . "{$invoiceLine}\n"
                 . "🏷️ VAT Collected: " . number_format($vatTotal, 2) . "\n"
                 . "🕐 Generated: " . Carbon::now()->format('d/m/Y H:i');

        $this->sendDocument($chatId, $filename, $pdf, $caption);
    }

    public function sendDailyReport(TelegramConfig $config): void
    {
        $start = Carbon::yesterday()->startOfDay();
        $end   = Carbon::yesterday()->endOfDay();
        $this->buildAndSendReport($start, $end, 'Daily', $config, 'daily_chat_id');
    }

    public function sendWeeklyReport(TelegramConfig $config): void
    {
        $start = Carbon::now()->subWeek()->startOfWeek();
        $end   = Carbon::now()->subWeek()->endOfWeek();
        $this->buildAndSendReport($start, $end, 'Weekly', $config, 'weekly_chat_id');
    }

    public function sendMonthlyReport(TelegramConfig $config): void
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end   = Carbon::now()->subMonth()->endOfMonth();
        $this->buildAndSendReport($start, $end, 'Monthly', $config, 'monthly_chat_id');
    }

    public function sendInvoiceCreatedAlert(Invoice $invoice): void
    {
        $type     = ucwords(str_replace('_', ' ', $invoice->invoice_type));
        $category = ucwords(str_replace('_', ' ', $invoice->payment_category));
        $by       = Auth::check() ? Auth::user()->name : 'System';
        $date     = $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('d/m/Y') : \Carbon\Carbon::parse($invoice->date)->format('d/m/Y');

        $caption = "🧾 *Invoice Created*\n"
                 . "📋 Type: {$type}\n"
                 . "🔢 Doc: `{$invoice->invoice_number}`\n"
                 . "📅 Date: {$date}\n"
                 . "👤 Customer: {$invoice->customer_name}\n"
                 . "🚘 Car: {$invoice->car_model}\n"
                 . "💰 Amount: {$invoice->currency} " . number_format((float) $invoice->grand_total, 2) . "\n"
                 . "🏷️ Category: {$category}\n"
                 . "👨‍💼 Created By: {$by}";

        $invoice->loadMissing('creator');
        $pdf      = $this->generateInvoicePdf($invoice);
        $filename = $invoice->invoice_number . '-created.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendReceiptCreatedAlert(Receipt $receipt): void
    {
        $category = ucwords(str_replace('_', ' ', $receipt->payment_category));
        $by       = Auth::check() ? Auth::user()->name : 'System';
        $date     = $receipt->date instanceof \Carbon\Carbon ? $receipt->date->format('d/m/Y') : \Carbon\Carbon::parse($receipt->date)->format('d/m/Y');

        $caption = "🧾 *Receipt Created*\n"
                 . "🔢 Doc: `{$receipt->receipt_number}`\n"
                 . "📅 Date: {$date}\n"
                 . "👤 Customer: {$receipt->customer_name}\n"
                 . "🚘 Car: {$receipt->car_model}\n"
                 . "💰 Amount: {$receipt->currency} " . number_format((float) $receipt->total_amount, 2) . "\n"
                 . "🏷️ Category: {$category}\n"
                 . "👨‍💼 Created By: {$by}";

        $receipt->loadMissing('creator');
        $pdf      = $this->generateReceiptPdf($receipt);
        $filename = $receipt->receipt_number . '-created.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendInvoiceDownloadAlert(Invoice $invoice): void
    {
        $type     = ucwords(str_replace('_', ' ', $invoice->invoice_type));
        $category = ucwords(str_replace('_', ' ', $invoice->payment_category));
        $by       = Auth::check() ? Auth::user()->name : 'System';
        $date     = $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('d/m/Y') : \Carbon\Carbon::parse($invoice->date)->format('d/m/Y');

        $caption = "📥 *Invoice Downloaded*\n"
                 . "📋 Type: {$type}\n"
                 . "🔢 Doc: `{$invoice->invoice_number}`\n"
                 . "📅 Date: {$date}\n"
                 . "👤 Customer: {$invoice->customer_name}\n"
                 . "🚘 Car: {$invoice->car_model}\n"
                 . "💰 Amount: {$invoice->currency} " . number_format((float) $invoice->grand_total, 2) . "\n"
                 . "🏷️ Category: {$category}\n"
                 . "👨‍💼 Downloaded By: {$by}";

        $invoice->loadMissing('creator');
        $pdf      = $this->generateInvoicePdf($invoice);
        $filename = $invoice->invoice_number . '-downloaded.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendInvoiceUpdatedAlert(Invoice $invoice): void
    {
        $type     = ucwords(str_replace('_', ' ', $invoice->invoice_type));
        $category = ucwords(str_replace('_', ' ', $invoice->payment_category));
        $by       = Auth::check() ? Auth::user()->name : 'System';
        $date     = $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('d/m/Y') : \Carbon\Carbon::parse($invoice->date)->format('d/m/Y');

        $caption = "✏️ *Invoice Updated*\n"
                 . "📋 Type: {$type}\n"
                 . "🔢 Doc: `{$invoice->invoice_number}`\n"
                 . "📅 Date: {$date}\n"
                 . "👤 Customer: {$invoice->customer_name}\n"
                 . "🚘 Car: {$invoice->car_model}\n"
                 . "💰 Amount: {$invoice->currency} " . number_format((float) $invoice->grand_total, 2) . "\n"
                 . "🏷️ Category: {$category}\n"
                 . "👨‍💼 Updated By: {$by}";

        $invoice->loadMissing('creator');
        $pdf      = $this->generateInvoicePdf($invoice);
        $filename = $invoice->invoice_number . '-updated.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendInvoiceDeletedAlert(string $invoiceNumber, string $customerName, string $carModel): void
    {
        $by  = Auth::check() ? Auth::user()->name : 'System';
        $now = Carbon::now()->format('d/m/Y H:i');

        $msg = "🗑️ *Invoice Deleted*\n"
             . "🔢 Doc: `{$invoiceNumber}`\n"
             . "👤 Customer: {$customerName}\n"
             . "🚘 Car: {$carModel}\n"
             . "👨‍💼 Deleted By: {$by}\n"
             . "🕐 At: {$now}";

        $this->broadcast($msg);
    }

    public function sendReceiptUpdatedAlert(Receipt $receipt): void
    {
        $category = ucwords(str_replace('_', ' ', $receipt->payment_category));
        $by       = Auth::check() ? Auth::user()->name : 'System';
        $date     = $receipt->date instanceof \Carbon\Carbon ? $receipt->date->format('d/m/Y') : \Carbon\Carbon::parse($receipt->date)->format('d/m/Y');

        $caption = "✏️ *Receipt Updated*\n"
                 . "🔢 Doc: `{$receipt->receipt_number}`\n"
                 . "📅 Date: {$date}\n"
                 . "👤 Customer: {$receipt->customer_name}\n"
                 . "🚘 Car: {$receipt->car_model}\n"
                 . "💰 Amount: {$receipt->currency} " . number_format((float) $receipt->total_amount, 2) . "\n"
                 . "🏷️ Category: {$category}\n"
                 . "👨‍💼 Updated By: {$by}";

        $receipt->loadMissing('creator');
        $pdf      = $this->generateReceiptPdf($receipt);
        $filename = $receipt->receipt_number . '-updated.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendReceiptDeletedAlert(string $receiptNumber, string $customerName, string $carModel): void
    {
        $by  = Auth::check() ? Auth::user()->name : 'System';
        $now = Carbon::now()->format('d/m/Y H:i');

        $msg = "🗑️ *Receipt Deleted*\n"
             . "🔢 Doc: `{$receiptNumber}`\n"
             . "👤 Customer: {$customerName}\n"
             . "🚘 Car: {$carModel}\n"
             . "👨‍💼 Deleted By: {$by}\n"
             . "🕐 At: {$now}";

        $this->broadcast($msg);
    }

    public function sendImportAlert(string $docType, int $succeeded, int $failed, string $userName): void
    {
        $now = Carbon::now()->format('d/m/Y H:i');

        $msg = "📤 *{$docType} Imported*\n"
             . "✅ Succeeded: {$succeeded}\n"
             . ($failed > 0 ? "❌ Failed: {$failed}\n" : "")
             . "👤 By: {$userName}\n"
             . "🕐 At: {$now}";

        $this->broadcast($msg);
    }

    public function sendPrintAlert(string $docType, string $docNumber, string $customerName, string $userName): void
    {
        $now = Carbon::now()->format('d/m/Y H:i');

        $msg = "*{$docType} Printed*\n"
             . "🔢 Doc: `{$docNumber}`\n"
             . "👤 Customer: {$customerName}\n"
             . "👨‍💼 Printed By: {$userName}\n"
             . "🕐 At: {$now}";

        $this->broadcast($msg);
    }

    public function sendReceiptDownloadAlert(Receipt $receipt): void
    {
        $category = ucwords(str_replace('_', ' ', $receipt->payment_category));
        $by       = Auth::check() ? Auth::user()->name : 'System';
        $date     = $receipt->date instanceof \Carbon\Carbon ? $receipt->date->format('d/m/Y') : \Carbon\Carbon::parse($receipt->date)->format('d/m/Y');

        $caption = "📥 *Receipt Downloaded*\n"
                 . "🔢 Doc: `{$receipt->receipt_number}`\n"
                 . "📅 Date: {$date}\n"
                 . "👤 Customer: {$receipt->customer_name}\n"
                 . "🚘 Car: {$receipt->car_model}\n"
                 . "💰 Amount: {$receipt->currency} " . number_format((float) $receipt->total_amount, 2) . "\n"
                 . "🏷️ Category: {$category}\n"
                 . "👨‍💼 Downloaded By: {$by}";

        $receipt->loadMissing('creator');
        $pdf      = $this->generateReceiptPdf($receipt);
        $filename = $receipt->receipt_number . '-downloaded.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendReportDownloadAlert(string $reportType, string $userName, string $format): void
    {
        $label  = ucwords($reportType);
        $format = strtoupper($format);
        $now    = Carbon::now()->format('d/m/Y H:i');

        $msg = "📥 *Report Downloaded*\n"
             . "📊 Type: {$label} Report\n"
             . "📄 Format: {$format}\n"
             . "👤 By: {$userName}\n"
             . "🕐 At: {$now}";

        $this->broadcast($msg);
    }

    // ─── PDF Helpers ─────────────────────────────────────────────────────────

    private function embedFonts(string $html): string
    {
        $html = preg_replace_callback(
            "/url\\(['\"]?file:\\/\\/([^'\"\\)]+\\.(?:ttf|woff2?|otf))['\"]?\\)/i",
            function ($matches) {
                $path = $matches[1];
                if (!file_exists($path)) return $matches[0];
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match($ext) { 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'otf' => 'font/opentype', default => 'font/truetype' };
                return "url('data:{$mime};base64," . base64_encode(file_get_contents($path)) . "')";
            },
            $html
        );
        $html = preg_replace_callback(
            "/src=['\"]file:\\/\\/([^'\"]+\\.(?:png|jpe?g|gif|webp|svg))['\"]?/i",
            function ($matches) {
                $path = $matches[1];
                if (!file_exists($path)) return $matches[0];
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match($ext) { 'jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', default => 'image/png' };
                return "src='data:{$mime};base64," . base64_encode(file_get_contents($path)) . "'";
            },
            $html
        );
        return $html;
    }

    private function htmlToPdf(string $html, string $format = 'A4', string $orientation = 'portrait'): string
    {
        $html = $this->embedFonts($html);
        return Browsershot::html($html)
            ->setNodeBinary(trim(shell_exec('which node')))
            ->setNpmBinary(trim(shell_exec('which npm')))
            ->format($format)
            ->landscape($orientation === 'landscape')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pdf();
    }

    private function generateInvoicePdf(Invoice $invoice): string
    {
        $html = view('pdf.invoice_single', compact('invoice'))->render();
        return $this->htmlToPdf($html);
    }

    private function generateReceiptPdf(Receipt $receipt): string
    {
        $html = view('pdf.receipt_single', compact('receipt'))->render();
        return $this->htmlToPdf($html);
    }

    private function generateReportPdf($receipts, $invoices, Carbon $start, Carbon $end): string
    {
        $html = view('pdf.report', compact('receipts', 'invoices', 'start', 'end'))->render();
        return $this->htmlToPdf($html, 'A4', 'landscape');
    }
}

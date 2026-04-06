<?php
namespace App\Services;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\TelegramConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private function broadcast(string $msg): void
    {
        $configs = TelegramConfig::where('is_active', true)->get();
        foreach ($configs as $c) {
            $this->send($c->bot_token, $c->chat_id, $msg);
        }
    }

    private function send(string $token, string $chatId, string $msg): void
    {
        try {
            $client = Http::timeout(10);
            $proxy  = config('services.telegram.proxy');
            if ($proxy) {
                $client = $client->withOptions(['proxy' => $proxy]);
            }
            $response = $client->post("https://api.telegram.org/bot{$token}/sendMessage", [
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

    private function broadcastDocument(string $filename, string $pdfContent, string $caption): void
    {
        $configs = TelegramConfig::where('is_active', true)->get();
        foreach ($configs as $c) {
            $this->sendDocument($c->bot_token, $c->chat_id, $filename, $pdfContent, $caption);
        }
    }

    private function sendDocument(string $token, string $chatId, string $filename, string $pdfContent, string $caption): void
    {
        try {
            $client = Http::timeout(30);
            $proxy  = config('services.telegram.proxy');
            if ($proxy) {
                $client = $client->withOptions(['proxy' => $proxy]);
            }
            $response = $client->attach('document', $pdfContent, $filename, ['Content-Type' => 'application/pdf'])
                ->post("https://api.telegram.org/bot{$token}/sendDocument", [
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
        $pdf      = Pdf::loadView('pdf.invoice_single', compact('invoice'))->output();
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
        $pdf      = Pdf::loadView('pdf.receipt_single', compact('receipt'))->output();
        $filename = $receipt->receipt_number . '-created.pdf';

        $this->broadcastDocument($filename, $pdf, $caption);
    }

    public function sendInvoiceDownloadAlert(Invoice $invoice): void
    {
        $type = ucwords(str_replace('_', ' ', $invoice->invoice_type));
        $by   = Auth::check() ? Auth::user()->name : 'System';
        $date = $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('d/m/Y') : \Carbon\Carbon::parse($invoice->date)->format('d/m/Y');

        $msg = "📥 *Invoice Downloaded*\n"
             . "📋 Type: {$type}\n"
             . "🔢 Doc: `{$invoice->invoice_number}`\n"
             . "📅 Date: {$date}\n"
             . "👤 Customer: {$invoice->customer_name}\n"
             . "🚘 Car: {$invoice->car_model}\n"
             . "💰 Amount: {$invoice->currency} " . number_format((float) $invoice->grand_total, 2) . "\n"
             . "👨‍💼 Downloaded By: {$by}";

        $this->broadcast($msg);
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
        $pdf      = Pdf::loadView('pdf.invoice_single', compact('invoice'))->output();
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
        $pdf      = Pdf::loadView('pdf.receipt_single', compact('receipt'))->output();
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

        $msg = "🖨️ *{$docType} Printed*\n"
             . "🔢 Doc: `{$docNumber}`\n"
             . "👤 Customer: {$customerName}\n"
             . "👨‍💼 Printed By: {$userName}\n"
             . "🕐 At: {$now}";

        $this->broadcast($msg);
    }

    public function sendReceiptDownloadAlert(Receipt $receipt): void
    {
        $by = Auth::check() ? Auth::user()->name : 'System';

        $msg = "📥 *Receipt Downloaded*\n"
             . "🔢 Doc: `{$receipt->receipt_number}`\n"
             . "👤 Customer: {$receipt->customer_name}\n"
             . "🚘 Car: {$receipt->car_model}\n"
             . "💰 Amount: {$receipt->currency} " . number_format((float) $receipt->total_amount, 2) . "\n"
             . "👨‍💼 Downloaded By: {$by}";

        $this->broadcast($msg);
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
}

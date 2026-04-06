<?php

namespace App\Imports;

use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\UploadedFile;

class ReceiptsImport
{
    /**
     * Parse file and return preview rows without saving anything.
     *
     * @return array<array{sheet:string, receipt_number:string, customer_name:string, date:string, car_model:string, quantity:int, currency:string, total_amount:float, payment_category:string, bank_reference:string, duplicate:bool, error?:string}>
     */
    public function preview(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (\Throwable $e) {
            return [[
                'sheet'            => $file->getClientOriginalName(),
                'receipt_number'   => '',
                'customer_name'    => '',
                'date'             => '',
                'car_model'        => '',
                'quantity'         => 0,
                'currency'         => 'USD',
                'total_amount'     => 0,
                'payment_category' => '',
                'bank_reference'   => '',
                'duplicate'        => false,
                'error'            => 'Cannot read file: ' . $e->getMessage(),
            ]];
        }

        $rows = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            try {
                $data = $this->parseSheet($sheet);
                $data['duplicate'] = Receipt::where('receipt_number', $data['receipt_number'])->exists();
                $rows[] = $data;
            } catch (\Throwable $e) {
                $rows[] = [
                    'sheet'            => $sheet->getTitle(),
                    'receipt_number'   => '',
                    'customer_name'    => '',
                    'date'             => '',
                    'car_model'        => '',
                    'quantity'         => 0,
                    'currency'         => 'USD',
                    'total_amount'     => 0,
                    'payment_category' => '',
                    'bank_reference'   => '',
                    'duplicate'        => false,
                    'error'            => $e->getMessage(),
                ];
            }
        }

        return $rows;
    }

    /**
     * Import receipts from one or more OR-template xlsx files.
     * Each worksheet is treated as one receipt.
     *
     * @return array<array{success:bool, receipt_number?:string, sheet?:string, error?:string}>
     */
    public function import(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (\Throwable $e) {
            return [[
                'success' => false,
                'sheet'   => $file->getClientOriginalName(),
                'error'   => 'Cannot read file: ' . $e->getMessage(),
            ]];
        }

        $results = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = $sheet->getTitle();
            try {
                $receipt = $this->saveSheet($sheet);
                $results[] = [
                    'success'        => true,
                    'receipt_number' => $receipt->receipt_number,
                    'sheet'          => $sheetName,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'success' => false,
                    'sheet'   => $sheetName,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function saveSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): Receipt
    {
        $data = $this->parseSheet($sheet);

        // ── Duplicate guard ───────────────────────────────────────────────────
        if (Receipt::where('receipt_number', $data['receipt_number'])->exists()) {
            throw new \RuntimeException("Receipt #{$data['receipt_number']} already exists.");
        }

        return Receipt::create([
            'receipt_number'   => $data['receipt_number'],
            'date'             => $data['date'],
            'customer_name'    => $data['customer_name'],
            'customer_phone'   => $data['customer_phone'] ?: null,
            'car_model'        => $data['car_model'],
            'unit_price'       => $data['unit_price'],
            'currency'         => $data['currency'],
            'quantity'         => $data['quantity'],
            'total_amount'     => $data['total_amount'],
            'payment_category' => $data['payment_category'],
            'bank_reference'   => $data['bank_reference'] ?: '-',
            'notes'            => $data['notes'],
            'created_by'       => Auth::id(),
        ]);
    }

    /**
     * Parse a single worksheet into a data array (does NOT save to DB).
     */
    private function parseSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $sheetName = $sheet->getTitle();

        // ── Receipt number (L6): "NO: OR-XXXX" ───────────────────────────────
        $receiptNumber = trim(preg_replace('/^NO:\s*/iu', '', $sheet->getCell('L6')->getFormattedValue()));

        // ── Customer name (B10): "... Received From: NAME" ───────────────────
        $b10raw   = $sheet->getCell('B10')->getFormattedValue();
        $colonPos = mb_strrpos($b10raw, ':');
        $customerName = $colonPos !== false
            ? trim(mb_substr($b10raw, $colonPos + 1))
            : trim($b10raw);
        $customerName = trim($customerName, "\xE2\x80\x8B\xE2\x80\x8C\xE2\x80\x8D \t\n\r");
        if ($customerName === '') {
            $customerName = 'Unknown';
        }

        // ── Date (K10): "... (Date) : 04-April-2026" ─────────────────────────
        $dateRaw = trim(preg_replace('/.*\(Date\)\s*:\s*/iu', '', $sheet->getCell('K10')->getFormattedValue()));
        try {
            $date = Carbon::parse($dateRaw)->format('Y-m-d');
        } catch (\Exception) {
            $date = now()->format('Y-m-d');
        }

        // ── Customer phone (B11) ──────────────────────────────────────────────
        $customerPhone = trim(preg_replace('/.*Customer number\s*:\s*/iu', '', $sheet->getCell('B11')->getFormattedValue()));

        // ── Bank reference (K12) ──────────────────────────────────────────────
        $bankRaw      = $sheet->getCell('K12')->getFormattedValue();
        $bankColonPos = mb_strrpos($bankRaw, ':');
        $bankReference = $bankColonPos !== false
            ? trim(mb_substr($bankRaw, $bankColonPos + 1))
            : '';
        $bankReference = ltrim($bankReference, "\xe2\x9c\x94\xe2\x9c\x93 \t");
        if ($bankReference === '') {
            $k11 = $sheet->getCell('K11')->getFormattedValue();
            $bankReference = str_contains($k11, '✔') || str_contains($k11, '✓') ? 'Cash' : '-';
        }

        // ── Payment category (row 14 checkmarks) ─────────────────────────────
        $paymentCategory = $this->detectPaymentCategory($sheet);

        // ── Car model (D19) ───────────────────────────────────────────────────
        $carModel = trim($sheet->getCell('D19')->getFormattedValue());
        if ($carModel === '') {
            throw new \RuntimeException("Sheet \"{$sheetName}\": car model is missing in cell D19.");
        }

        // ── Quantity (K19) ────────────────────────────────────────────────────
        $quantity = (int) filter_var($sheet->getCell('K19')->getFormattedValue(), FILTER_SANITIZE_NUMBER_INT);
        if ($quantity < 1) {
            $quantity = 1;
        }

        // ── Currency detection ────────────────────────────────────────────────
        $amountCell = $sheet->getCell('M19')->getFormattedValue() ?: $sheet->getCell('M32')->getFormattedValue();
        $currency   = str_contains($amountCell, '៛') ? 'KHR' : 'USD';

        // ── Unit price & total ────────────────────────────────────────────────
        $unitPrice   = $this->parseAmount($sheet->getCell('L19')->getFormattedValue());
        $totalAmount = $this->parseAmount($sheet->getCell('M32')->getFormattedValue());

        if ($unitPrice <= 0 && $totalAmount > 0) {
            $unitPrice = round($totalAmount / $quantity, 2);
        }
        if ($totalAmount <= 0 && $unitPrice > 0) {
            $totalAmount = round($unitPrice * $quantity, 2);
        }

        // ── Notes / VIN (D20) ─────────────────────────────────────────────────
        $notes = trim($sheet->getCell('D20')->getFormattedValue()) ?: null;

        // ── Auto-generate receipt number if blank ─────────────────────────────
        if ($receiptNumber === '' || $receiptNumber === 'OR-' || $receiptNumber === 'OR') {
            $datePart = Carbon::parse($date)->format('Ymd');
            $prefix   = "REC-{$datePart}-";
            $last     = Receipt::withTrashed()->where('receipt_number', 'like', "{$prefix}%")->orderBy('receipt_number', 'desc')->first();
            $seq      = $last ? ((int) substr($last->receipt_number, -3)) + 1 : 1;
            $receiptNumber = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
        }

        return [
            'sheet'            => $sheetName,
            'receipt_number'   => $receiptNumber,
            'customer_name'    => $customerName,
            'customer_phone'   => $customerPhone,
            'date'             => $date,
            'car_model'        => $carModel,
            'quantity'         => $quantity,
            'currency'         => $currency,
            'unit_price'       => $unitPrice,
            'total_amount'     => $totalAmount,
            'payment_category' => $paymentCategory,
            'bank_reference'   => $bankReference,
            'notes'            => $notes,
        ];
    }

    private function detectPaymentCategory(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): string
    {
        $map = [
            'D' => 'booking',
            'F' => 'service_payment',
            'H' => 'down_payment',
            'J' => 'installment',
            'L' => 'full_payment',
        ];

        foreach ($map as $col => $category) {
            $val = trim($sheet->getCell($col . '14')->getFormattedValue());
            if (in_array($val, ['✔', '✓', 'x', 'X', '1', 'v', 'V'], true)) {
                return $category;
            }
        }

        return 'other';
    }

    private function parseAmount(string $raw): float
    {
        $clean = preg_replace('/[^0-9.]/', '', $raw);
        return $clean !== '' ? (float) $clean : 0.0;
    }
}

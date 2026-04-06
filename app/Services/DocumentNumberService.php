<?php
namespace App\Services;
use App\Models\Receipt;
use App\Models\Invoice;
use Carbon\Carbon;
class DocumentNumberService {
    public function generateReceiptNumber(): string {
        $date = Carbon::today()->format('Ymd');
        $prefix = "REC-{$date}-";
        $last = Receipt::withTrashed()->where('receipt_number','like',"{$prefix}%")->orderBy('receipt_number','desc')->first();
        $seq = $last ? ((int)substr($last->receipt_number,-3))+1 : 1;
        return $prefix.str_pad($seq,3,'0',STR_PAD_LEFT);
    }
    public function generateInvoiceNumber(string $type): string {
        $date = Carbon::today()->format('Ymd');
        $typeCode = $type==='car_sale'?'SALE':'SVC';
        $prefix = "INV-{$typeCode}-{$date}-";
        $last = Invoice::withTrashed()->where('invoice_number','like',"{$prefix}%")->orderBy('invoice_number','desc')->first();
        $seq = $last ? ((int)substr($last->invoice_number,-3))+1 : 1;
        return $prefix.str_pad($seq,3,'0',STR_PAD_LEFT);
    }
}

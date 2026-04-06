<?php
namespace App\Exports;
use App\Models\Receipt;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
class ReceiptsExport implements FromQuery, WithHeadings, WithMapping {
    private int $i=0;
    public function __construct(private ?string $from, private ?string $to) {}
    public function query() { $q=Receipt::query(); if($this->from)$q->whereDate('date','>=',$this->from); if($this->to)$q->whereDate('date','<=',$this->to); return $q->orderBy('date','desc'); }
    public function headings(): array { return ['No.','Receipt Number','Date','Customer Name','Phone','Car Model','Currency','Unit Price','Qty','Total Amount','Payment Category','Bank Reference','Notes']; }
    public function map($r): array { return [++$this->i,$r->receipt_number,$r->date->format('Y-m-d'),$r->customer_name,$r->customer_phone,$r->car_model,$r->currency,$r->unit_price,$r->quantity,$r->total_amount,$r->payment_category,$r->bank_reference,$r->notes]; }
}

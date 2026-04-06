<?php
namespace App\Exports;
use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
class InvoicesExport implements FromQuery, WithHeadings, WithMapping {
    private int $i=0;
    public function __construct(private ?string $from, private ?string $to, private ?string $type) {}
    public function query() { $q=Invoice::query(); if($this->from)$q->whereDate('date','>=',$this->from); if($this->to)$q->whereDate('date','<=',$this->to); if($this->type)$q->where('invoice_type',$this->type); return $q->orderBy('date','desc'); }
    public function headings(): array { return ['No.','Invoice Number','Type','Date','Customer Name','Address','Phone','Car Model','Currency','Unit Price','Qty','Sub-Total','VAT','VAT Amount','Grand Total','Payment Category','Bank Reference','Notes']; }
    public function map($r): array { return [++$this->i,$r->invoice_number,$r->invoice_type,$r->date->format('Y-m-d'),$r->customer_name,$r->customer_address,$r->customer_phone,$r->car_model,$r->currency,$r->unit_price,$r->quantity,$r->sub_total,$r->with_vat?'Yes':'No',$r->vat_amount,$r->grand_total,$r->payment_category,$r->bank_reference,$r->notes]; }
}

<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
class ReportExport implements WithMultipleSheets {
    public function __construct(private ?string $from, private ?string $to) {}
    public function sheets(): array { return ['Receipts'=>new ReceiptsExport($this->from,$this->to),'Invoices'=>new InvoicesExport($this->from,$this->to,null)]; }
}

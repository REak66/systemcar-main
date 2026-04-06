<?php

namespace App\Exports;

use App\Models\Receipt;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ReceiptTemplateExport implements WithEvents, WithTitle
{
    public function __construct(private Receipt $receipt) {}

    public function title(): string
    {
        return $this->receipt->customer_name ?? 'Receipt';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->build($event->sheet->getDelegate());
            },
        ];
    }

    private function build(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        $r = $this->receipt;
        $curr = $r->currency === 'KHR' ? '៛' : '$';

        $fmt = fn($n) => number_format((float) $n, 2);
        $fmtDate = function ($d) {
            try {
                return \Carbon\Carbon::parse($d)->format('d-F-Y');
            } catch (\Exception) {
                return '';
            }
        };

        // ── Column widths ──────────────────────────────────────────────────────
        $widths = [
            'A' => 2,  'B' => 14, 'C' => 10, 'D' => 12, 'E' => 10,
            'F' => 4,  'G' => 10, 'H' => 4,  'I' => 10, 'J' => 4,
            'K' => 12, 'L' => 12, 'M' => 14,
        ];
        foreach ($widths as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }

        // ── Row heights ────────────────────────────────────────────────────────
        $ws->getRowDimension(7)->setRowHeight(20);
        $ws->getRowDimension(8)->setRowHeight(22);
        for ($i = 19; $i <= 31; $i++) {
            $ws->getRowDimension($i)->setRowHeight(22);
        }

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 2-4  Company header
        // ═══════════════════════════════════════════════════════════════════════
        $ws->mergeCells('F2:M2');
        $ws->setCellValue('F2', 'Huan Ya He Zhong (Cambodia) Trading Co., Ltd');
        $ws->getStyle('F2')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('E3:M3');
        $ws->setCellValue('E3', 'Address: Lot No. 52 National Road 6A Phum Dermkor, S. Chroychongva, K. Chroychongva, Phnom Penh');
        $ws->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('E4:M4');
        $ws->setCellValue('E4', 'Telephone: 023 886 687 ; Facebook Page: BYD Cambodia');
        $ws->getStyle('E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 6  Receipt number
        // ═══════════════════════════════════════════════════════════════════════
        $ws->mergeCells('L6:M6');
        $ws->setCellValue('L6', 'NO: ' . $r->receipt_number);
        $ws->getStyle('L6')->getFont()->setBold(true);
        $ws->getStyle('L6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 7-8  Title
        // ═══════════════════════════════════════════════════════════════════════
        $ws->mergeCells('B7:M7');
        $ws->setCellValue('B7', 'បង្កាន់ដៃទទួលប្រាក់');
        $ws->getStyle('B7')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('B8:M8');
        $ws->setCellValue('B8', 'OFFICIAL RECEIPT');
        $ws->getStyle('B8')->getFont()->setBold(true)->setSize(14);
        $ws->getStyle('B8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Separator
        $ws->getStyle('B9:M9')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 10-12  Customer info
        // ═══════════════════════════════════════════════════════════════════════
        $ws->mergeCells('B10:J10');
        $ws->setCellValue('B10', 'បានទទួលពី /Received From: ' . $r->customer_name);

        $ws->mergeCells('K10:M10');
        $ws->setCellValue('K10', 'កាលបរិច្ឆេទ (Date) : ' . $fmtDate($r->date));
        $ws->getStyle('K10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $ws->mergeCells('B11:J11');
        $ws->setCellValue('B11', 'លេខទូរស័ព្ទអតិថិជន/ Customer number : ' . ($r->customer_phone ?? ''));

        $cashCheck    = ($r->payment_category !== 'booking' && !str_contains(strtolower($r->bank_reference ?? ''), 'aba') && !str_contains(strtolower($r->bank_reference ?? ''), 'bank')) ? ' ✔' : '';
        $transferCheck = $cashCheck === '' ? ' ✔' : '';
        $ws->mergeCells('K11:M11');
        $ws->setCellValue('K11', 'សាច់ប្រាក់ / Cash :' . $cashCheck);
        $ws->getStyle('K11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $ws->mergeCells('B12:J12');
        $ws->setCellValue('B12', 'ផ្នែកលក់/Sales:  ' . ($r->creator?->name ?? ''));

        $ws->mergeCells('K12:M12');
        $ws->setCellValue('K12', 'ផ្ទេរតាមធនាគារ / Bank Transfer:' . $transferCheck . ' ' . ($r->bank_reference ?? ''));
        $ws->getStyle('K12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 14  Payment stage checkboxes
        // ═══════════════════════════════════════════════════════════════════════
        $ws->getStyle('B14:M14')->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_THIN);

        $stages = [
            'B' => '1. First Deposit', 'check_B' => 'D',  'cat_B' => 'booking',
            'E' => '2. Blind',         'check_E' => 'F',  'cat_E' => 'service_payment',
            'G' => '3. Down',          'check_G' => 'H',  'cat_G' => 'down_payment',
            'I' => '4. Final',         'check_I' => 'J',  'cat_I' => 'installment',
            'K' => '5. Full',          'check_K' => 'L',  'cat_K' => 'full_payment',
        ];

        $labelMap = [
            'B' => ['label' => '1. First Deposit', 'check' => 'D', 'cat' => 'booking'],
            'E' => ['label' => '2. Blind',          'check' => 'F', 'cat' => 'service_payment'],
            'G' => ['label' => '3. Down',           'check' => 'H', 'cat' => 'down_payment'],
            'I' => ['label' => '4. Final',          'check' => 'J', 'cat' => 'installment'],
            'K' => ['label' => '5. Full',           'check' => 'L', 'cat' => 'full_payment'],
        ];

        foreach ($labelMap as $col => $info) {
            $ws->setCellValue($col . '14', $info['label']);
            if ($r->payment_category === $info['cat']) {
                $ws->setCellValue($info['check'] . '14', '✔');
                $ws->getStyle($info['check'] . '14')->getFont()->setBold(true);
            }
        }

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 17-18  Table headers
        // ═══════════════════════════════════════════════════════════════════════
        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
        ];

        $ws->mergeCells('B17:B18');
        $ws->setCellValue('B17', "No\nល.រ");
        $ws->mergeCells('C17:J18');
        $ws->setCellValue('C17', "Description\nបរិយាយមុខទំនិញ");
        $ws->mergeCells('K17:K18');
        $ws->setCellValue('K17', "Quantity\nបរិមាណ");
        $ws->mergeCells('L17:L18');
        $ws->setCellValue('L17', "Unit Price\nថ្លៃឯកតា");
        $ws->mergeCells('M17:M18');
        $ws->setCellValue('M17', "Amount\nថ្លៃទំនិញ");

        foreach (['B17:B18', 'C17:J18', 'K17:K18', 'L17:L18', 'M17:M18'] as $range) {
            $ws->getStyle($range)->applyFromArray($headerStyle);
        }

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 19-20  Item row (car model + VIN)
        // ═══════════════════════════════════════════════════════════════════════
        $borderThin = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

        $ws->setCellValue('B19', '1');
        $ws->getStyle('B19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('C19:J20');
        $ws->setCellValue('C19', 'Model: ' . $r->car_model . "\nVIN: " . ($r->notes ?? ''));
        $ws->getStyle('C19:J20')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        $ws->mergeCells('B19:B20');
        $ws->setCellValue('K19', $r->quantity);
        $ws->getStyle('K19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ws->setCellValue('L19', $curr . ' ' . $fmt($r->unit_price));
        $ws->getStyle('L19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $ws->setCellValue('M19', $curr . ' ' . $fmt($r->total_amount));
        $ws->getStyle('M19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle('M19')->getFont()->setBold(true);

        // Borders on item rows 19-31 visible columns
        for ($row = 19; $row <= 31; $row++) {
            $ws->getStyle("B{$row}:M{$row}")->applyFromArray($borderThin);
        }

        // Merge K20:K31 (qty spacer), L20:L31 (price spacer), M20:M31 (amount spacer)
        $ws->mergeCells('K20:K31');
        $ws->mergeCells('L20:L31');
        $ws->mergeCells('M20:M31');

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 32-33  Balance to pay
        // ═══════════════════════════════════════════════════════════════════════
        $ws->mergeCells('B32:L32');
        $ws->setCellValue('B32', 'Balance to Pay');
        $ws->getStyle('B32')->getFont()->setBold(true);
        $ws->getStyle('B32')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->setCellValue('M32', $curr . ' ' . $fmt($r->total_amount));
        $ws->getStyle('M32')->getFont()->setBold(true);
        $ws->getStyle('M32')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle('B32:M32')->applyFromArray($borderThin);

        $ws->mergeCells('B33:L33');
        $ws->setCellValue('B33', 'ទឹកប្រាក់ដែលត្រូវបង់ (Balance to Pay)');
        $ws->getStyle('B33')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->setCellValue('M33', $curr . ' ' . $fmt($r->total_amount));
        $ws->getStyle('M33')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle('B33:M33')->applyFromArray($borderThin);

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 35-37  Bank details
        // ═══════════════════════════════════════════════════════════════════════
        $ws->setCellValue('B35', 'Bank Name');
        $ws->mergeCells('D35:M35');
        $ws->setCellValue('D35', 'ADVANCED BANK OF ASIA LIMITED');

        $ws->setCellValue('B36', 'Beneficiary Name');
        $ws->mergeCells('D36:M36');
        $ws->setCellValue('D36', 'HUAN YA HE ZHONG (CAMBODIA) TRADING CO LTD');

        $ws->setCellValue('B37', 'Account Number');
        $ws->mergeCells('D37:M37');
        $ws->setCellValue('D37', '002886771(USD)');

        foreach (['B35', 'B36', 'B37'] as $cell) {
            $ws->getStyle($cell)->getFont()->setBold(true);
        }

        // ═══════════════════════════════════════════════════════════════════════
        // ROW 41-50  Signatures
        // ═══════════════════════════════════════════════════════════════════════
        $ws->mergeCells('C41:F41');
        $ws->setCellValue('C41', 'ទទួលដោយ៖');
        $ws->getStyle('C41')->getFont()->setBold(true);

        $ws->mergeCells('L41:M41');
        $ws->setCellValue('L41', 'ហត្ថលេខាអតិថិជន៖');
        $ws->getStyle('L41')->getFont()->setBold(true);

        $ws->mergeCells('C42:F42');
        $ws->setCellValue('C42', 'Received By:');

        $ws->mergeCells('L42:M42');
        $ws->setCellValue('L42', "Customer's Signature");

        // Signature lines
        $ws->getStyle('C48:F48')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle('L48:M48')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        $ws->mergeCells('C49:F49');
        $ws->setCellValue('C49', 'ឈ្មោះ (姓名): ' . ($r->creator?->name ?? ''));

        $ws->mergeCells('L49:M49');
        $ws->setCellValue('L49', 'ឈ្មោះ (姓名):');

        $ws->mergeCells('C50:F50');
        $ws->setCellValue('C50', 'កាលបរិច្ឆេទ (日期): ' . $fmtDate($r->date));

        $ws->mergeCells('L50:M50');
        $ws->setCellValue('L50', 'កាលបរិច្ឆេទ (日期):');

        // ── Global sheet settings ─────────────────────────────────────────────
        $ws->getPageSetup()->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);
        $ws->getDefaultRowDimension()->setRowHeight(16);
        $ws->getParent()->getDefaultStyle()->getFont()->setName('Khmer OS Siemreap')->setSize(10);
    }
}

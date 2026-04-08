<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'KhmerOS';
            src: url('file://{{ public_path("fonts/KhmerOS.ttf") }}') format('truetype');
        }

        * {
            font-family: 'KhmerOS', sans-serif;
        }

        @page {
            margin: 15mm;
        }

        body {
            font-family: 'KhmerOS', sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            line-height: 1.5;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .header-left {
            display: table-cell;
            vertical-align: top;
        }

        .byd-badge {
            display: inline-block;
            background: #dc2626;
            color: #fff;
            font-weight: bold;
            font-size: 20px;
            padding: 2px 7px;
            letter-spacing: 4px;
            line-height: 1.1;
            margin-right: 10px;
            vertical-align: top;
            margin-top: -3px;
        }

        .company-wrap {
            display: inline-block;
            vertical-align: top;
            text-align: center;
        }

        .company-name {
            font-weight: bold;
            font-size: 12px;
            line-height: 1.3;
        }

        .company-sub {
            color: #4b5563;
            font-size: 9px;
            line-height: 1.4;
        }

        hr {
            border: none;
            border-top: 1px solid #6b7280;
            margin: 5px 0 6px;
        }

        .doc-title {
            text-align: center;
            position: relative;
            margin: 6px 0;
        }

        .doc-title-kh {
            font-family: 'KhmerOS', sans-serif !important;
            font-size: 11px;
            font-weight: bold;
        }

        .doc-title-en {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .doc-no {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 9px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #9ca3af;
            margin-bottom: 0;
        }

        table.info td {
            padding: 4px 8px;
            font-size: 9.5px;
        }

        table.info .label {
            color: #4b5563;
        }

        table.stages {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #9ca3af;
            border-top: none;
            margin-bottom: 12px;
        }

        table.stages td {
            padding: 5px 10px;
        }

        .stage-item {
            display: inline-block;
            margin-right: 18px;
            font-size: 9.5px;
        }

        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #4b5563;
            text-align: center;
            line-height: 11px;
            font-size: 9px;
            vertical-align: middle;
            margin-right: 3px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #9ca3af;
        }

        table.items th {
            border: 1px solid #9ca3af;
            padding: 5px 4px;
            text-align: center;
            font-size: 9px;
        }

        table.items td {
            border-left: 1px solid #9ca3af;
            border-right: 1px solid #9ca3af;
            padding: 4px 5px;
            font-size: 9.5px;
        }

        table.items td.center {
            text-align: center;
        }

        table.items td.right {
            text-align: right;
            white-space: nowrap;
        }

        table.items tfoot td {
            border: 1px solid #9ca3af;
            padding: 5px 7px;
            font-size: 9.5px;
        }

        .bank-info {
            margin-top: 10px;
            font-size: 9.5px;
            line-height: 1.7;
        }

        .bank-info .label {
            color: #4b5563;
            margin-right: 6px;
        }

        .signatures {
            display: table;
            width: 100%;
            margin-top: 36px;
        }

        .sig-block {
            display: table-cell;
            text-align: center;
            width: 50%;
            font-size: 9.5px;
            vertical-align: top;
        }

        .sig-line {
            border-top: 1px solid #9ca3af;
            padding-top: 4px;
            margin-top: 28px;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="byd-badge">BYD</div>
            <div class="company-wrap">
                <div class="company-name">Huan Ya He Zhong (Cambodia) Trading Co., Ltd</div>
                <div class="company-sub">Address: Lot No 52 Phum Derntkov, S. Chroychongva, K. Chroychongva, Phnum Penh
                </div>
                <div class="company-sub">Telephone: 023 886 687 ; Facebook Page: BYD Cambodia</div>
            </div>
        </div>
    </div>

    <hr>

    {{-- Document Title --}}
    <div class="doc-title">
        <div class="doc-title-kh">បង្កាន់ដៃទទួលប្រាក់</div>
        <div class="doc-title-en">
            {{ $invoice->invoice_type === 'car_sale' ? 'OFFICIAL RECEIPT' : 'SERVICE INVOICE' }}
        </div>
        <span class="doc-no">NO: {{ $invoice->invoice_number }}</span>
    </div>

    {{-- Info Table --}}
    <table class="info">
        <tr>
            <td style="width:50%">
                <span class="label">បានទទួលពី /Received From:</span>
                <strong> {{ $invoice->customer_name }}</strong>
            </td>
            <td style="text-align:right">
                <span class="label">កាលបរិច្ឆេទ (Date) :</span>
                <strong> {{ $invoice->date->format('d-M-Y') }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">លេខទូរស័ព្ទអតិថិជន/ Customer number :</span>
                <span>{{ $invoice->customer_phone }}</span>
            </td>
            <td style="text-align:right">
                <span class="label">សាច់ប្រាក់ / Cash :</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">អ្នកលក់/Sales:</span>
                <span>{{ $invoice->creator?->name }}</span>
            </td>
            <td style="text-align:right">
                <span class="label">ផ្ទេរតាមធនាគារ /Bank Transfer: ✓</span>
                <strong> {{ $invoice->bank_reference }}</strong>
            </td>
        </tr>
        <tr style="height:16px;">
            <td></td>
            <td></td>
        </tr>
    </table>

    {{-- Payment Stage Checkboxes --}}
    @php
    $stages = [
    'booking' => '1. First Deposit',
    'service_payment' => '2. Blind',
    'down_payment' => '3. Down',
    'installment' => '4. Final',
    'full_payment' => '5. Full',
    ];
    @endphp
    <table class="stages">
        <tr>
            <td>
                @foreach($stages as $id => $label)
                <span class="stage-item">
                    <span class="checkbox">{{ $invoice->payment_category === $id ? '✓' : '' }}</span>
                    {{ $label }}
                </span>
                @endforeach
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:24px;">No<br><span style="font-family:'KhmerOS',sans-serif;font-size:8px;">ល.រ</span>
                </th>
                <th>Description<br><span style="font-family:'KhmerOS',sans-serif;font-size:8px;">បរិយាយមុខទំនិញ</span>
                </th>
                <th style="width:48px;">Quantity<br><span
                        style="font-family:'KhmerOS',sans-serif;font-size:8px;">បរិមាណ</span></th>
                <th style="width:100px;">Unit Price<br><span
                        style="font-family:'KhmerOS',sans-serif;font-size:8px;">ថ្លៃឯកតា</span></th>
                <th style="width:90px;">Amount<br><span
                        style="font-family:'KhmerOS',sans-serif;font-size:8px;">ថ្លៃទំនិញ</span></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center" style="vertical-align: top; padding-top: 5px;">1</td>
                <td style="vertical-align: top; padding-top: 5px; min-height: 80px;">
                    <div>Model: <span>{{ $invoice->car_model }}</span></div>
                    <div style="margin-top:2px;">VIN: <span>{{ $invoice->notes ?? '' }}</span></div>
                </td>
                <td class="center" style="vertical-align: top; padding-top: 5px;">{{ $invoice->quantity }}</td>
                <td class="right" style="vertical-align: top; padding-top: 5px;">
                    @if($invoice->unit_price > 0)
                    {{ $invoice->currency === 'KHR' ? '៛' : '$' }} {{ number_format($invoice->unit_price, 2) }}
                    @endif
                </td>
                <td class="right" style="vertical-align: top; padding-top: 5px;">
                    {{ $invoice->currency === 'KHR' ? '៛' : '$' }} {{ number_format($invoice->sub_total, 2) }}
                </td>
            </tr>
            {{-- spacer rows --}}
            @for($r = 0; $r < 4; $r++) <tr style="height:32px;">
                <td style="border-left:1px solid #999;border-right:1px solid #999;"></td>
                <td style="border-left:1px solid #999;border-right:1px solid #999;"></td>
                <td style="border-left:1px solid #999;border-right:1px solid #999;"></td>
                <td style="border-left:1px solid #999;border-right:1px solid #999;"></td>
                <td style="border-left:1px solid #999;border-right:1px solid #999;"></td>
                </tr>
                @endfor
        </tbody>
        <tfoot>
            @if($invoice->with_vat)
            <tr>
                <td colspan="4" style="text-align:right;">VAT ({{ $invoice->vat_rate }}%)</td>
                <td class="right">{{ $invoice->currency === 'KHR' ? '៛' : '$' }} {{ number_format($invoice->vat_amount,
                    2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="4" style="text-align:right; font-weight:bold;">Balance to Pay</td>
                <td class="right" style="font-weight:bold;">
                    {{ $invoice->currency === 'KHR' ? '៛' : '$' }} {{ number_format($invoice->grand_total, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right; font-weight:bold;">ទឹកប្រាក់ដែលត្រូវបង់ (Balance to Pay)</td>
                <td class="right" style="font-weight:bold;">
                    {{ $invoice->currency === 'KHR' ? '៛' : '$' }} {{ number_format($invoice->grand_total, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Bank Info --}}
    <div class="bank-info">
        <div><span class="label">Bank Name</span>ADVANCED BANK OF ASIA LIMITED</div>
        <div><span class="label">Beneficiary Name</span>HUAN YA HE ZHONG (CAMBODIA) TRADING CO LTD</div>
        <div><span class="label">Account Number</span>002886771(USD)</div>
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-block">
            <div style="font-weight:bold; margin-bottom:4px;">ទទួលដោយ:</div>
            <div style="margin-bottom:28px;">Received By:</div>
            <div class="sig-line">
                <div>ឈ្មោះ: <span>{{ $invoice->creator?->name }}</span></div>
                <div>កាលបរិច្ឆេទ: {{ $invoice->date->format('d-M-Y') }}</div>
            </div>
        </div>
        <div class="sig-block">
            <div style="font-weight:bold; margin-bottom:4px;">ហត្ថលេខាអតិថិជន:</div>
            <div style="margin-bottom:28px;">Customer's Signature</div>
            <div class="sig-line">
                <div>ឈ្មោះ:</div>
                <div>កាលបរិច្ឆេទ:</div>
            </div>
        </div>
    </div>

</body>

</html>
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

        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            font-size: 9px;
            white-space: nowrap;
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

        .doc-title {
            text-align: center;
            margin: 5px 0 4px;
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

        hr {
            border: none;
            border-top: 1px solid #6b7280;
            margin: 5px 0 8px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        table.info td {
            padding: 2px 4px;
            font-size: 9.5px;
        }

        table.info .label {
            color: #4b5563;
        }

        .stages {
            border: 1px solid #9ca3af;
            padding: 5px 10px;
            margin-bottom: 12px;
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

        .sig-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .sig-sub {
            margin-bottom: 28px;
        }

        .sig-line {
            border-top: 1px solid #9ca3af;
            padding-top: 4px;
            margin-top: 0;
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
        <div class="header-right">NO: {{ $receipt->receipt_number }}</div>
    </div>

    {{-- Document Title --}}
    <div class="doc-title">
        <div class="doc-title-kh">បង្កាន់ដៃទទួលប្រាក់</div>
        <div class="doc-title-en">OFFICIAL RECEIPT</div>
    </div>

    <hr>

    {{-- Info Table --}}
    <table class="info">
        <tr>
            <td style="width:50%; padding-right:8px;">
                <span class="label">បានទទួលពី /Received From:</span>
                <strong> {{ $receipt->customer_name }}</strong>
            </td>
            <td style="text-align:right;">
                <span class="label">កាលបរិច្ឆេទ /Date :</span>
                <strong> {{ $receipt->date->format('d-m-y') }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding-right:8px;">
                <span class="label">លេខទូរស័ព្ទអតិថិជន/ Customer number :</span>
                <span> {{ $receipt->customer_phone }}</span>
            </td>
            <td style="text-align:right;">
                <span class="label">សាច់ប្រាក់ /Cash :</span>
            </td>
        </tr>
        <tr>
            <td style="padding-right:8px;">
                <span class="label">អ្នកលក់/Sales:</span>
                <span> {{ $receipt->creator?->name }}</span>
            </td>
            <td style="text-align:right;">
                <span class="label">ផ្ទេរតាមធនាគារ /Bank Transfer: ✓</span>
                <strong> {{ $receipt->bank_reference }}</strong>
            </td>
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
    <div class="stages">
        @foreach($stages as $id => $label)
        <span class="stage-item">
            <span class="checkbox">{{ $receipt->payment_category === $id ? '✓' : '' }}</span>
            {{ $label }}
        </span>
        @endforeach
    </div>

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
                    <div>Model: <span>{{ $receipt->car_model }}</span></div>
                    <div style="margin-top:2px;">VIN: <span>{{ $receipt->notes ?? '' }}</span></div>
                </td>
                <td class="center" style="vertical-align: top; padding-top: 5px;">{{ $receipt->quantity }}</td>
                <td class="right" style="vertical-align: top; padding-top: 5px;">
                    @if($receipt->unit_price > 0)
                    {{ $receipt->currency === 'KHR' ? '៛' : '$' }} {{ number_format($receipt->unit_price, 2) }}
                    @endif
                </td>
                <td class="right" style="vertical-align: top; padding-top: 5px;">
                    {{ $receipt->currency === 'KHR' ? '៛' : '$' }} {{ number_format($receipt->total_amount, 2) }}
                </td>
            </tr>
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
            <tr>
                <td colspan="4" style="text-align:right; font-weight:bold;">Balance to Pay</td>
                <td class="right" style="font-weight:bold;">
                    {{ $receipt->currency === 'KHR' ? '៛' : '$' }} {{ number_format($receipt->total_amount, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right; font-weight:bold;">ចំនួនបង់ (Balance to Pay)</td>
                <td class="right" style="font-weight:bold;">
                    {{ $receipt->currency === 'KHR' ? '៛' : '$' }} {{ number_format($receipt->total_amount, 2) }}
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
            <div class="sig-title">ទទួលដោយ</div>
            <div class="sig-sub">Received By:</div>
            <div class="sig-line">
                <div>ឈ្មោះ: <span>{{ $receipt->creator?->name }}</span></div>
                <div>កាលបរិច្ឆេទ: {{ $receipt->date->format('d-m-y') }}</div>
            </div>
        </div>
        <div class="sig-block">
            <div class="sig-title">ហត្ថលេខាអតិថិជន</div>
            <div class="sig-sub">Customer's Signature</div>
            <div class="sig-line">
                <div>ឈ្មោះ:</div>
                <div>កាលបរិច្ឆេទ:</div>
            </div>
        </div>
    </div>

</body>

</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'KhmerOS';
            src: url('{{ public_path('fonts/KhmerOS.ttf') }}') format('truetype');
        }
        body { font-family: 'KhmerOS', sans-serif; font-size: 11px; color: #333; margin: 20px; }
        h1 { font-size: 18px; color: #1e40af; margin-bottom: 4px; }
        h2 { font-size: 13px; color: #374151; margin: 16px 0 6px; }
        .subtitle { color: #666; font-size: 11px; margin-bottom: 10px; }
        .summary { display: flex; gap: 20px; margin-bottom: 16px; }
        .card { background: #eff6ff; border: 1px solid #bfdbfe; padding: 8px 14px; border-radius: 6px; min-width: 110px; }
        .card .label { font-size: 9px; color: #6b7280; }
        .card .value { font-size: 14px; font-weight: bold; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { background: #1e40af; color: #fff; padding: 5px 7px; text-align: left; font-size: 9px; }
        td { padding: 4px 7px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .number { text-align: right; }
        .total-row td { font-weight: bold; background: #eff6ff; border-top: 2px solid #1e40af; }
        .footer { margin-top: 20px; font-size: 9px; color: #999; text-align: center; }
        .section { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Car System — Financial Report</h1>
    <p class="subtitle">
        Period: {{ \Carbon\Carbon::parse($start)->format('d-m-y') }} → {{ \Carbon\Carbon::parse($end)->format('d-m-y') }} | Generated: {{ now()->format('d-m-y H:i:s') }} | Bank Transfer Only
    </p>

    @php
        $receiptTotal = $receipts->sum('total_amount');
        $invoiceTotal = $invoices->sum('grand_total');
        $vatTotal = $invoices->sum('vat_amount');
    @endphp

    <!-- Summary -->
    <table style="width:auto;margin-bottom:16px;">
        <tr>
            <td style="padding:4px 12px;border:1px solid #bfdbfe;background:#eff6ff;">Receipts Count</td>
            <td style="padding:4px 12px;border:1px solid #bfdbfe;font-weight:bold;">{{ $receipts->count() }}</td>
            <td style="padding:4px 12px;border:1px solid #bfdbfe;background:#eff6ff;">Invoices Count</td>
            <td style="padding:4px 12px;border:1px solid #bfdbfe;font-weight:bold;">{{ $invoices->count() }}</td>
            <td style="padding:4px 12px;border:1px solid #bfdbfe;background:#eff6ff;">Total VAT</td>
            <td style="padding:4px 12px;border:1px solid #bfdbfe;font-weight:bold;">{{ number_format($vatTotal, 2) }}</td>
        </tr>
    </table>

    <!-- Receipts Section -->
    <div class="section">
        <h2>Receipts</h2>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Car Model</th>
                    <th>Qty</th>
                    <th>CCY</th>
                    <th class="number">Amount</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                @php $r_usd = 0; $r_khr = 0; @endphp
                @forelse($receipts as $i => $r)
                    @php if ($r->currency === 'USD') $r_usd += $r->total_amount; else $r_khr += $r->total_amount; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r->receipt_number }}</td>
                        <td>{{ $r->date->format('d-m-y') }}</td>
                        <td>{{ $r->customer_name }}</td>
                        <td>{{ $r->car_model }}</td>
                        <td>{{ $r->quantity }}</td>
                        <td>{{ $r->currency }}</td>
                        <td class="number">{{ number_format($r->total_amount, 2) }}</td>
                        <td>{{ str_replace('_', ' ', $r->payment_category) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;color:#999;">No receipts</td></tr>
                @endforelse
            </tbody>
            @if($receipts->count())
            <tfoot>
                <tr class="total-row">
                    <td colspan="7">TOTAL (USD)</td>
                    <td class="number">{{ number_format($r_usd, 2) }}</td>
                    <td></td>
                </tr>
                @if($r_khr > 0)
                <tr class="total-row">
                    <td colspan="7">TOTAL (KHR)</td>
                    <td class="number">{{ number_format($r_khr, 2) }}</td>
                    <td></td>
                </tr>
                @endif
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Invoices Section -->
    <div class="section">
        <h2>Invoices</h2>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Invoice No.</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Car Model</th>
                    <th>CCY</th>
                    <th class="number">Sub-Total</th>
                    <th>VAT</th>
                    <th class="number">Grand Total</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                @php $inv_usd = 0; $inv_khr = 0; @endphp
                @forelse($invoices as $i => $inv)
                    @php if ($inv->currency === 'USD') $inv_usd += $inv->grand_total; else $inv_khr += $inv->grand_total; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $inv->invoice_number }}</td>
                        <td>{{ str_replace('_', ' ', $inv->invoice_type) }}</td>
                        <td>{{ $inv->date->format('d-m-y') }}</td>
                        <td>{{ $inv->customer_name }}</td>
                        <td>{{ $inv->car_model }}</td>
                        <td>{{ $inv->currency }}</td>
                        <td class="number">{{ number_format($inv->sub_total, 2) }}</td>
                        <td>{{ $inv->with_vat ? $inv->vat_rate . '%' : '—' }}</td>
                        <td class="number">{{ number_format($inv->grand_total, 2) }}</td>
                        <td>{{ str_replace('_', ' ', $inv->payment_category) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="text-align:center;color:#999;">No invoices</td></tr>
                @endforelse
            </tbody>
            @if($invoices->count())
            <tfoot>
                <tr class="total-row">
                    <td colspan="9">TOTAL (USD)</td>
                    <td class="number">{{ number_format($inv_usd, 2) }}</td>
                    <td></td>
                </tr>
                @if($inv_khr > 0)
                <tr class="total-row">
                    <td colspan="9">TOTAL (KHR)</td>
                    <td class="number">{{ number_format($inv_khr, 2) }}</td>
                    <td></td>
                </tr>
                @endif
            </tfoot>
            @endif
        </table>
    </div>

    <div class="footer">Cashier Management Car System — Bank Transfer Only</div>
</body>
</html>

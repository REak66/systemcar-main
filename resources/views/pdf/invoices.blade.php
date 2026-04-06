<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; margin: 20px; }
        h1 { font-size: 16px; color: #1e40af; margin-bottom: 4px; }
        .subtitle { color: #666; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1e40af; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .number { text-align: right; }
        .total-row td { font-weight: bold; background: #eff6ff; border-top: 2px solid #1e40af; }
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 9px; }
        .footer { margin-top: 20px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>Car System — Invoices</h1>
    <p class="subtitle">Generated: {{ now()->format('d-m-y H:i:s') }} | Bank Transfer Only</p>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Invoice No.</th>
                <th>Type</th>
                <th>Date</th>
                <th>Customer Name</th>
                <th>Car Model</th>
                <th>Currency</th>
                <th class="number">Sub-Total</th>
                <th>VAT</th>
                <th class="number">Grand Total</th>
                <th>Category</th>
            </tr>
        </thead>
        <tbody>
            @php $total_usd = 0; $total_khr = 0; @endphp
            @forelse($invoices as $i => $inv)
                @php
                    if ($inv->currency === 'USD') $total_usd += $inv->grand_total;
                    else $total_khr += $inv->grand_total;
                @endphp
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
                <tr><td colspan="11" style="text-align:center;padding:20px;color:#999;">No data found</td></tr>
            @endforelse
        </tbody>
        @if($invoices->count())
        <tfoot>
            <tr class="total-row">
                <td colspan="9">TOTAL (USD)</td>
                <td class="number">{{ number_format($total_usd, 2) }}</td>
                <td></td>
            </tr>
            @if($total_khr > 0)
            <tr class="total-row">
                <td colspan="9">TOTAL (KHR)</td>
                <td class="number">{{ number_format($total_khr, 2) }}</td>
                <td></td>
            </tr>
            @endif
        </tfoot>
        @endif
    </table>

    <div class="footer">Cashier Management Car System — Bank Transfer Only</div>
</body>
</html>

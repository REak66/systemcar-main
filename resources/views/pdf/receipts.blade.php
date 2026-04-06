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
        .footer { margin-top: 20px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>Car System — Receipts</h1>
    <p class="subtitle">Generated: {{ now()->format('d-m-y H:i:s') }} | Bank Transfer Only</p>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Receipt No.</th>
                <th>Date</th>
                <th>Customer Name</th>
                <th>Car Model</th>
                <th>Qty</th>
                <th>Currency</th>
                <th class="number">Total Amount</th>
                <th>Category</th>
                <th>Bank Reference</th>
            </tr>
        </thead>
        <tbody>
            @php $total_usd = 0; $total_khr = 0; @endphp
            @forelse($receipts as $i => $r)
                @php
                    if ($r->currency === 'USD') $total_usd += $r->total_amount;
                    else $total_khr += $r->total_amount;
                @endphp
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
                    <td>{{ $r->bank_reference }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:20px;color:#999;">No data found</td></tr>
            @endforelse
        </tbody>
        @if($receipts->count())
        <tfoot>
            <tr class="total-row">
                <td colspan="7">TOTAL (USD)</td>
                <td class="number">{{ number_format($total_usd, 2) }}</td>
                <td colspan="2"></td>
            </tr>
            @if($total_khr > 0)
            <tr class="total-row">
                <td colspan="7">TOTAL (KHR)</td>
                <td class="number">{{ number_format($total_khr, 2) }}</td>
                <td colspan="2"></td>
            </tr>
            @endif
        </tfoot>
        @endif
    </table>

    <div class="footer">Cashier Management Car System — Bank Transfer Only</div>
</body>
</html>

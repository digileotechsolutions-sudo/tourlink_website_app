<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }} | Havenedge Tourlink</title>
    <style>
        body { margin: 0; padding: 24px; color: #172033; font: 14px/1.5 Arial, sans-serif; background: #f8fafc; }
        .receipt { max-width: 760px; margin: 24px auto; padding: 40px; border: 1px solid #dbe2ea; background: #fff; }
        h1, h2, p { margin-top: 0; } h1 { color: #1f0052; font-size: 25px; }
        .eyebrow { color: #1f0052; font-size: 11px; font-weight: bold; letter-spacing: .12em; text-transform: uppercase; }
        dl { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin: 24px 0; }
        dt { color: #64748b; font-size: 12px; } dd { margin: 2px 0 0; font-weight: bold; overflow-wrap: anywhere; }
        .amount { margin: 24px 0; padding: 18px; background: #f5f0fa; font-size: 19px; font-weight: bold; }
        .actions { display: flex; justify-content: center; gap: 12px; margin: 20px auto; }
        button, .download { min-height: 42px; padding: 0 16px; border: 0; border-radius: 8px; color: #fff; background: #1f0052; font-weight: bold; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; }
        @media (max-width: 600px) { body { padding: 0; } .receipt { margin: 0; padding: 24px 18px; border: 0; } dl { grid-template-columns: 1fr 1fr; } }
        @media print { body { padding: 0; background: #fff; } .receipt { max-width: none; margin: 0; padding: 14mm; border: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print receipt</button>
        <a class="download" href="{{ route('payments.receipt.download', $payment) }}">Download receipt</a>
    </div>
    <main class="receipt">
        <p class="eyebrow">Havenedge Tourlink</p>
        <h1>Payment receipt</h1>
        <p>Receipt number: <strong>{{ $payment->receipt_number }}</strong></p>
        <dl>
            <div><dt>Booking reference</dt><dd>{{ $payment->booking->reference }}</dd></div>
            <div><dt>Customer</dt><dd>{{ $payment->booking->traveler?->name }}</dd></div>
            <div><dt>Customer phone</dt><dd>{{ $payment->booking->traveler?->phone }}</dd></div>
            <div><dt>Trip / vehicle</dt><dd>{{ $payment->booking->trip?->name ?? $payment->booking->vehicle?->name ?? 'TourLink booking' }}</dd></div>
            <div><dt>Payment method</dt><dd>{{ str($payment->payment_method?->value ?? $payment->provider)->replace('_', ' ')->title() }}</dd></div>
            <div><dt>Payment reference</dt><dd>{{ $payment->transaction_reference ?: $payment->merchant_reference }}</dd></div>
            <div><dt>Payment date</dt><dd>{{ $payment->paid_at?->format('M j, Y H:i') }}</dd></div>
            <div><dt>Payment status</dt><dd>{{ str($payment->status->value)->replace('_', ' ')->title() }}</dd></div>
            @if ($payment->receiver)<div><dt>Received by</dt><dd>{{ $payment->receiver->name }}</dd></div>@endif
        </dl>
        <p class="amount">Amount paid: {{ $payment->booking->currency }} {{ number_format($payment->amount) }}</p>
        <dl>
            <div><dt>Booking total</dt><dd>{{ $payment->booking->currency }} {{ number_format($summary['total']) }}</dd></div>
            <div><dt>Previously paid</dt><dd>{{ $payment->booking->currency }} {{ number_format($previousPaid) }}</dd></div>
            <div><dt>Remaining balance</dt><dd>{{ $payment->booking->currency }} {{ number_format($summary['balance']) }}</dd></div>
        </dl>
        <p>Thank you for choosing Havenedge Tourlink.</p>
    </main>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xendit Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #111827; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .ok { color: #047857; margin-bottom: 12px; }
        .err { color: #b91c1c; margin-bottom: 12px; }
        label { font-size: 13px; color: #6b7280; display: block; margin-bottom: 4px; }
        input, textarea, select { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; margin-bottom: 10px; }
        button { background: #111827; color: #fff; border: 0; border-radius: 6px; padding: 10px 14px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 14px; }
    </style>
</head>
<body>
<h1>Xendit Payment Test Page</h1>

@if(session('success'))
    <div class="ok">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="err">{{ $errors->first() }}</div>
@endif

<div class="grid">
    <div class="card">
        <h3>Create Invoice</h3>
        <form method="POST" action="{{ route('xendit.invoices.create') }}">
            @csrf
            <input type="hidden" name="source" value="xendit-test-page">

            <label for="location_id">Location ID</label>
            <input id="location_id" name="location_id" type="text" placeholder="loc-123" required>

            <label for="amount">Amount</label>
            <input id="amount" name="amount" type="number" min="1000" step="1" placeholder="150000" required>

            <label for="currency">Currency</label>
            <input id="currency" name="currency" type="text" value="IDR" maxlength="3">

            <label for="payer_email">Payer Email</label>
            <input id="payer_email" name="payer_email" type="email" placeholder="customer@example.com">

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="2" placeholder="Test invoice"></textarea>

            <button type="submit">Create Xendit Invoice</button>
        </form>
    </div>

    <div class="card">
        <h3>Simulate Webhook</h3>
        <form method="POST" action="{{ route('xendit.test.simulate-webhook') }}">
            @csrf

            <label for="invoice_id">Invoice ID (optional)</label>
            <input id="invoice_id" name="invoice_id" type="text" placeholder="inv-123">

            <label for="external_id">External ID (optional)</label>
            <input id="external_id" name="external_id" type="text" placeholder="ghl-loc-123-uuid">

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="PENDING">PENDING</option>
                <option value="PAID">PAID</option>
                <option value="SETTLED">SETTLED</option>
                <option value="EXPIRED">EXPIRED</option>
                <option value="FAILED">FAILED</option>
            </select>

            <label for="invoice_url">Invoice URL (optional)</label>
            <input id="invoice_url" name="invoice_url" type="url" placeholder="https://checkout.xendit.co/...">

            <button type="submit">Apply Webhook Simulation</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <h3>Recent Payments</h3>
    @if($payments->isEmpty())
        <p>No payments yet.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Location</th>
                <th>External ID</th>
                <th>Invoice ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Invoice URL</th>
            </tr>
            </thead>
            <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->location_id }}</td>
                    <td>{{ $payment->external_id }}</td>
                    <td>{{ $payment->xendit_invoice_id ?? '-' }}</td>
                    <td>{{ $payment->currency }} {{ number_format((float) $payment->amount, 0) }}</td>
                    <td>{{ $payment->status }}</td>
                    <td>
                        @if($payment->invoice_url)
                            <a href="{{ $payment->invoice_url }}" target="_blank" rel="noopener noreferrer">Open</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

</body>
</html>

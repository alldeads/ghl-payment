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
        .action-link { display: inline-block; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #111827; font-size: 12px; }
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
</div>

<div class="card" style="margin-top:16px;">
    <h3>Recent Xendit Transactions</h3>
    @if(empty($transactions))
        <p>No transactions found or unable to fetch from Xendit.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Invoice ID</th>
                <th>External ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Invoice URL</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction['id'] ?? '-' }}</td>
                    <td>{{ $transaction['external_id'] ?? '-' }}</td>
                    <td>{{ ($transaction['currency'] ?? 'IDR') }} {{ number_format((float) ($transaction['amount'] ?? 0), 0) }}</td>
                    <td>{{ $transaction['status'] ?? '-' }}</td>
                    <td>
                        @if(!empty($transaction['invoice_url']))
                            <a href="{{ $transaction['invoice_url'] }}" target="_blank" rel="noopener noreferrer">Open</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if(!empty($transaction['id']))
                            <a class="action-link" href="{{ route('xendit.test.checkout', ['invoiceId' => $transaction['id']]) }}">Card Checkout</a>
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

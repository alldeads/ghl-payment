<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>GHL App Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #111827; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; max-width: 720px; }
        .ok { color: #047857; }
        .err { color: #b91c1c; }
        .label { color: #6b7280; font-size: 13px; }
        .value { margin-bottom: 10px; }
        input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        button { padding: 10px 14px; border-radius: 6px; border: 0; background: #111827; color: #fff; cursor: pointer; }
    </style>
</head>
<body>
<h1>GoHighLevel Marketplace App</h1>

@if(($connected ?? false) === true)
    <div class="box">
        <p class="ok"><strong>Status:</strong> Connected</p>

        @if(isset($installation))
            <div class="label">Location ID</div>
            <div class="value">{{ $installation->location_id }}</div>

            <div class="label">Company ID</div>
            <div class="value">{{ $installation->company_id ?? 'N/A' }}</div>

            <div class="label">Scopes</div>
            <div class="value">{{ $installation->scopes ?? 'N/A' }}</div>

            <div class="label">Token Expires At</div>
            <div class="value">{{ optional($installation->expires_at)->toDateTimeString() ?? 'N/A' }}</div>
        @endif

        @if(isset($location) && is_array($location))
            <hr>
            <h3>Location Details</h3>
            <div class="label">Name</div>
            <div class="value">{{ $location['name'] ?? 'N/A' }}</div>

            <div class="label">Email</div>
            <div class="value">{{ $location['email'] ?? 'N/A' }}</div>

            <div class="label">Phone</div>
            <div class="value">{{ $location['phone'] ?? 'N/A' }}</div>
        @endif

        <hr>
        <h3>Create Xendit Invoice</h3>
        <form method="POST" action="{{ route('xendit.invoices.create') }}">
            @csrf
            <input type="hidden" name="location_id" value="{{ $installation->location_id ?? request('locationId') }}">

            <label class="label" for="amount">Amount (IDR)</label>
            <input id="amount" type="number" name="amount" min="1000" step="1" required>

            <label class="label" for="payer_email">Payer Email</label>
            <input id="payer_email" type="email" name="payer_email" placeholder="customer@email.com">

            <label class="label" for="description">Description</label>
            <textarea id="description" name="description" rows="2" placeholder="Invoice from GHL Xendit gateway"></textarea>

            <button type="submit">Create Invoice</button>
        </form>

        <p><a href="{{ route('xendit.test') }}">View transactions directly from Xendit</a></p>
    </div>
@else
    <div class="box">
        <p class="err"><strong>Status:</strong> Not connected</p>
        <p>{{ $error ?? 'Install this app from GoHighLevel Marketplace to get started.' }}</p>
        <p><a href="{{ route('ghl.install') }}">Install / Reconnect App</a></p>
    </div>
@endif

</body>
</html>

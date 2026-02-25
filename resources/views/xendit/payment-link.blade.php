<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Payment Link — Xendit</title>
    <style>
        :root { color-scheme: light; }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 32px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back:hover { color: #0f172a; }
        h1 { margin: 0 0 6px; font-size: 22px; }
        .subtitle { margin: 0 0 24px; font-size: 14px; color: #475569; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #374151;
        }
        .field { margin-bottom: 16px; }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #fff;
            transition: border-color .15s;
        }
        input:focus, select:focus { border-color: #0f172a; }
        .currency-row {
            display: grid;
            grid-template-columns: 1fr 100px;
            gap: 10px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #0f172a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 4px;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #b91c1c;
            margin-bottom: 16px;
        }
        .hint { font-size: 12px; color: #94a3b8; margin-top: 3px; }
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        .powered {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .powered span { font-weight: 600; color: #64748b; }
    </style>
</head>
<body>
<div class="card">
    <a href="{{ url('/') }}" class="back">&#8592; Back to home</a>

    <h1>Create Payment Link</h1>
    <p class="subtitle">Generate a Xendit-hosted payment page and share it with your customer.</p>

    @if ($errors->any())
        <div class="error-box">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('xendit.payment-link.create') }}">
        @csrf

        <div class="field currency-row">
            <div>
                <label for="amount">Amount</label>
                <input
                    id="amount"
                    name="amount"
                    type="number"
                    min="1000"
                    step="1"
                    placeholder="e.g. 50000"
                    value="{{ old('amount') }}"
                    required
                />
                <p class="hint">Minimum: 1,000</p>
            </div>
            <div>
                <label for="currency">Currency</label>
                <select id="currency" name="currency">
                    <option value="PHP" {{ old('currency', 'PHP') === 'PHP' ? 'selected' : '' }}>PHP</option>
                    <option value="IDR" {{ old('currency') === 'IDR' ? 'selected' : '' }}>IDR</option>
                    <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD</option>
                    <option value="SGD" {{ old('currency') === 'SGD' ? 'selected' : '' }}>SGD</option>
                    <option value="MYR" {{ old('currency') === 'MYR' ? 'selected' : '' }}>MYR</option>
                    <option value="THB" {{ old('currency') === 'THB' ? 'selected' : '' }}>THB</option>
                    <option value="VND" {{ old('currency') === 'VND' ? 'selected' : '' }}>VND</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="payer_email">Customer Email</label>
            <input
                id="payer_email"
                name="payer_email"
                type="email"
                placeholder="customer@example.com"
                value="{{ old('payer_email') }}"
                required
            />
        </div>

        <div class="field">
            <label for="description">Description <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
            <input
                id="description"
                name="description"
                type="text"
                placeholder="e.g. Order #1234"
                value="{{ old('description') }}"
                maxlength="255"
            />
        </div>

        <hr class="divider">

        <button type="submit" class="btn">Generate Payment Link &rarr;</button>
    </form>

    <p class="powered" style="margin-top:20px;">Powered by <span>Xendit</span></p>
</div>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GHL x Xendit Gateway</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }
        .hero {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 18px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.2;
        }
        .sub {
            margin: 0;
            color: #334155;
            line-height: 1.6;
        }
        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            background: #fff;
            font-weight: 600;
            font-size: 14px;
        }
        .btn.primary {
            background: #0f172a;
            color: #fff;
            border-color: #0f172a;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
            margin-top: 14px;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
        }
        h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        ul, ol {
            margin: 0;
            padding-left: 18px;
            color: #334155;
            line-height: 1.6;
        }
        code {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 1px 6px;
            font-size: 12px;
        }
        .note {
            margin-top: 14px;
            font-size: 13px;
            color: #475569;
        }
    </style>
</head>
<body>
<div class="container">
    <section class="hero">
        <h1>GoHighLevel + Xendit Payment Gateway</h1>
        <p class="sub">
            This app connects your GHL location with Xendit invoice payments.
            Install from marketplace, create invoice links, and process payment status updates via webhooks.
        </p>
        <div class="cta-row">
            <a class="btn primary" href="{{ route('ghl.install') }}">Connect GHL App</a>
            <a class="btn" href="{{ route('xendit.test') }}">Open Xendit Test Page</a>
            <a class="btn" href="{{ route('ghl.dashboard') }}">Open App Dashboard</a>
        </div>
        <p class="note">If dashboard shows “No location selected”, open it from GHL launch URL where <code>locationId</code> is passed automatically.</p>
    </section>

    <div class="grid">
        <section class="card">
            <h2>How to connect in GHL</h2>
            <ol>
                <li>Create/update your Marketplace App in GHL.</li>
                <li>Set OAuth Redirect URL to <code>{{ url('/ghl/callback') }}</code>.</li>
                <li>Set App Launch URL to <code>{{ url('/ghl/dashboard') }}</code>.</li>
                <li>Set Scopes to match your enabled features.</li>
                <li>Install the app to a location and click Connect.</li>
            </ol>
        </section>

        <section class="card">
            <h2>Webhook setup</h2>
            <ul>
                <li>GHL webhook URL: <code>{{ url('/ghl/webhook') }}</code></li>
                <li>Xendit webhook URL: <code>{{ url('/xendit/webhook') }}</code></li>
                <li>Set <code>XENDIT_CALLBACK_TOKEN</code> in your .env.</li>
                <li>Use the same token in Xendit callback configuration.</li>
            </ul>
        </section>

        <section class="card">
            <h2>Required env keys</h2>
            <ul>
                <li><code>GHL_CLIENT_ID</code>, <code>GHL_CLIENT_SECRET</code>, <code>GHL_REDIRECT_URI</code></li>
                <li><code>XENDIT_SECRET_KEY</code>, <code>XENDIT_CALLBACK_TOKEN</code></li>
                <li><code>GHL_WEBHOOK_SECRET</code> if using GHL webhook verification</li>
            </ul>
        </section>

        <section class="card">
            <h2>Quick test flow</h2>
            <ol>
                <li>Open <a href="{{ route('xendit.test') }}">Xendit Test Page</a>.</li>
                <li>Create an invoice with location ID and amount.</li>
                <li>Open <code>Card Checkout</code> for an invoice.</li>
                <li>Complete card flow and verify response from Xendit.</li>
            </ol>
        </section>
    </div>
</div>
</body>
</html>

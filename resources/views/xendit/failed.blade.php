<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Xendit Payment Failed</title>
        <style>
            body {
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                background: #f3f4f6;
                color: #111827;
            }

            .card {
                width: min(560px, calc(100% - 2rem));
                background: #ffffff;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                padding: 2rem;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            }

            h1 {
                margin: 0 0 0.75rem;
                font-size: 1.5rem;
            }

            p {
                margin: 0;
                color: #374151;
                line-height: 1.5;
            }
        </style>
    </head>
    <body>
        <main class="card">
            <h1>Payment failed</h1>
            <p>The Xendit payment could not be completed. Please try again.</p>
        </main>
    </body>
</html>

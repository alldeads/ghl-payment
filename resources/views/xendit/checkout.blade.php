<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Xendit Checkout (React)</title>
        <script>
            window.__XENDIT_CONFIG__ = {
                publishableKey: @json((string) env('XENDIT_PUBLIC_KEY')),
            };
        </script>
        @vite(['resources/css/app.css', 'resources/js/xendit-checkout.jsx'])
    </head>
    <body class="min-h-screen bg-gray-300 antialiased">
        <div id="xendit-checkout-root"></div>

        <script src="https://js.xendit.co/v1/xendit.min.js"></script>
    </body>
</html>

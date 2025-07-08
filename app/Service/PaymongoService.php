<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;

class PaymongoService
{
    private $secret;
    private $baseUrl = 'https://api.paymongo.com/v1';

    public function __construct()
    {
        $this->secret = config('services.paymongo.secret');
    }

    protected function client()
    {
        return Http::withBasicAuth($this->secret, '')
            ->acceptJson()
            ->baseUrl($this->baseUrl);
    }

    public function getPaymentMethods()
    {
        $response = $this->client()->get('merchants/capabilities/payment_methods');
        return $response->json();
    }

    public function checkoutSession()
    {
        $response = $this->client()->post('checkout_sessions', [
            'data' => [
                'attributes' => [
                    'amount' => 10000, // Amount in cents
                    'currency' => 'PHP',
                    'payment_method_types' => ['card', 'gcash'],
                    'success_url' => url('/'),
                    'cancel_url' => url('/'),
                    'description' => 'Sample Checkout Session',
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'line_items' => [
                        [
                            'currency' => 'PHP',
                            'name' => 'Sample Item',
                            'amount' => 10000, // Amount in cents
                            'quantity' => 1,
                            'description' => 'This is a sample item for checkout.',
                        ]
                    ]
                ]
            ]
        ]);
        return $response->json();
    }

    public function createPaymentIntent($amount, $currency = 'PHP')
    {
        $response = $this->client()->post('/payment_intents', [
            'data' => [
                'attributes' => [
                    'amount' => $amount * 100,
                    'payment_method_allowed' => ['card', 'gcash'],
                    'currency' => $currency,
                ]
            ]
        ]);

        return $response->json();
    }
}

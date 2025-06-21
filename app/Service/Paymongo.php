<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;

class Paymongo
{
    private $secret;

    public function __construct()
    {
        $this->secret = config('services.paymongo.secret');
    }

    protected function client()
    {
        return Http::withBasicAuth($this->secret, '')
            ->acceptJson()
            ->baseUrl('https://api.paymongo.com/v1');
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

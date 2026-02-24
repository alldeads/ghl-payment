<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class XenditService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function createInvoice(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.xendit.api_base_url'), '/');
        $secretKey = (string) config('services.xendit.secret_key');

        return Http::baseUrl($baseUrl)
            ->withBasicAuth($secretKey, '')
            ->acceptJson()
            ->post('/v2/invoices', $payload)
            ->throw()
            ->json();
    }

    public function isValidCallbackToken(?string $callbackToken): bool
    {
        $expectedToken = (string) config('services.xendit.callback_token');

        if ($expectedToken === '' || ! is_string($callbackToken) || $callbackToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $callbackToken);
    }
}

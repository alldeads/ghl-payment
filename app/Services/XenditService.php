<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class XenditService
{
    private function client()
    {
        $baseUrl = rtrim((string) config('services.xendit.api_base_url'), '/');
        $secretKey = (string) config('services.xendit.secret_key');

        return Http::baseUrl($baseUrl)
            ->withBasicAuth($secretKey, '')
            ->acceptJson();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function createInvoice(array $payload): array
    {
        return $this->client()
            ->post('/v2/invoices', $payload)
            ->throw()
            ->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listInvoices(int $limit = 20): array
    {
        $response = $this->client()
            ->get('/v2/invoices', [
                'limit' => max(1, min($limit, 100)),
            ])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getInvoice(string $invoiceId): array
    {
        return $this->client()
            ->get('/v2/invoices/'.urlencode($invoiceId))
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function chargeCard(array $payload): array
    {
        return $this->client()
            ->post('/credit_card_charges', $payload)
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function chargeEwallet(array $payload): array
    {
        $request = $this->client();
        $callbackUrl = trim((string) config('services.xendit.callback_url'));

        if ($callbackUrl !== '') {
            $request = $request->withHeaders([
                'with-callback-url' => $callbackUrl,
            ]);
        }

        return $request
            ->post('/ewallets/charges', $payload)
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

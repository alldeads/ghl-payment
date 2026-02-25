<?php

namespace App\Services;

use App\Enums\CheckoutMethod;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XenditService
{
    public const CURRENCY = 'PHP';

    public const EWALLET = 'PH_GCASH';

    public $callbackUrl = null;

    public function __construct()
    {
        $this->callbackUrl = url('/xendit/webhook');
    }

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
        $payload = [
            'reference_id' => 'ewallet-' . Str::orderedUuid(),
            'currency' => self::CURRENCY,
            'request_amount' => (float) $payload['amount'],
            'checkout_method' => CheckoutMethod::ONE_TIME_PAYMENT->value,
            'channel_code' => self::EWALLET,
            'callback_url' => $this->callbackUrl,
            'channel_properties' => [
                'success_redirect_url' => route('xendit.success'),
                'failure_redirect_url' => route('xendit.failed'),
            ],
        ];

        return $this->client()
            ->post('/ewallets/charges', $payload)
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createQrCode(array $payload): array
    {
        $request = $this->client();
        $callbackUrl = trim((string) config('services.xendit.callback_url'));

        if ($callbackUrl !== '') {
            $request = $request->withHeaders([
                'with-callback-url' => $callbackUrl,
            ]);
        }

        return $request
            ->post('/qr_codes', $payload)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function simulateQrPayment(string $qrCodeId): array
    {
        return $this->client()
            ->post('/qr_codes/'.urlencode($qrCodeId).'/payments/simulate')
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

<?php

namespace App\Http\Controllers;

use App\Services\XenditService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class XenditPaymentController extends Controller
{
    public function __construct(private readonly XenditService $xenditService)
    {
    }

    public function testPage(): View
    {
        $transactions = [];

        try {
            $transactions = $this->xenditService->listInvoices(20);
        } catch (Throwable) {
            $transactions = [];
        }

        return view('xendit.test', [
            'transactions' => $transactions,
        ]);
    }

    public function createInvoice(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payer_email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $externalId = 'ghl-'.$validated['location_id'].'-'.Str::uuid();

        $payload = [
            'external_id' => $externalId,
            'amount' => (float) $validated['amount'],
            'currency' => strtoupper((string) ($validated['currency'] ?? 'PHP')),
            'payer_email' => $validated['payer_email'] ?? null,
            'description' => $validated['description'] ?? 'Invoice from GHL Xendit gateway',
            'success_redirect_url' => config('services.xendit.success_redirect_url'),
            'failure_redirect_url' => config('services.xendit.failure_redirect_url'),
            'invoice_duration' => (int) config('services.xendit.invoice_duration_seconds', 86400),
            'metadata' => [
                'location_id' => $validated['location_id'],
                'source' => 'ghl-marketplace-app',
                ...($validated['metadata'] ?? []),
            ],
        ];

        try {
            $invoice = $this->xenditService->createInvoice($payload);

            if (! $request->expectsJson()) {
                if ((string) $request->input('source') === 'xendit-test-page') {
                    return redirect()->route('xendit.test')->with('success', 'Invoice created successfully on Xendit.');
                }

                $locationId = (string) $validated['location_id'];

                return redirect()->route('ghl.dashboard', ['locationId' => $locationId]);
            }

            return response()->json([
                'ok' => true,
                'external_id' => $invoice['external_id'] ?? $externalId,
                'xendit_invoice_id' => $invoice['id'] ?? null,
                'status' => $invoice['status'] ?? null,
                'invoice_url' => $invoice['invoice_url'] ?? null,
                'xendit' => $invoice,
            ], 201);
        } catch (Throwable $exception) {
            if (! $request->expectsJson()) {
                return redirect()->back()->withErrors([
                    'xendit' => 'Failed to create invoice: '.$exception->getMessage(),
                ]);
            }

            return response()->json([
                'ok' => false,
                'message' => 'Failed to create Xendit invoice.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    public function simulatedCheckout(?string $invoiceId = null): View
    {
        return view('xendit.checkout');
    }

    public function payWithCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:20'],
            'token_id' => ['required', 'string', 'max:255'],
            'authentication_id' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $chargePayload = [
                'amount' => (float) $validated['amount'],
                'token_id' => $validated['token_id'],
                'authentication_id' => $validated['authentication_id'],
                'currency' => strtoupper((string) ($validated['currency'] ?? 'PHP')),
                'external_id' => $validated['external_id'] ?? Str::orderedUuid()->toString(),
            ];

            $charge = $this->xenditService->chargeCard($chargePayload);

            return response()->json($charge);
        } catch (RequestException $exception) {
            return response()->json([
                'message' => json_encode($exception->response->json() ?? [
                    'message' => $exception->getMessage(),
                    'errors' => [],
                ], JSON_THROW_ON_ERROR),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function payViaEwallet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'checkout_method' => ['required', 'string', 'max:50'],
            'channel_code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $payload = [
                'reference_id' => 'ewallet-'.Str::orderedUuid(),
                'currency' => strtoupper((string) $validated['currency']),
                'amount' => (float) $validated['amount'],
                'checkout_method' => $validated['checkout_method'],
                'channel_code' => $validated['channel_code'],
                'channel_properties' => [
                    'success_redirect_url' => url('/ewallet/success'),
                    'failure_redirect_url' => url('/ewallet/failed'),
                ],
            ];

            $charge = $this->xenditService->chargeEwallet($payload);

            return response()->json($charge);
        } catch (RequestException $exception) {
            return response()->json([
                'message' => json_encode($exception->response->json() ?? [
                    'message' => $exception->getMessage(),
                    'errors' => [],
                ], JSON_THROW_ON_ERROR),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}

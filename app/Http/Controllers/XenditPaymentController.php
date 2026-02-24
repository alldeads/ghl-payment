<?php

namespace App\Http\Controllers;

use App\Models\XenditPayment;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class XenditPaymentController extends Controller
{
    public function __construct(private readonly XenditService $xenditService)
    {
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
            'currency' => strtoupper((string) ($validated['currency'] ?? 'IDR')),
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

            $payment = XenditPayment::query()->create([
                'location_id' => $validated['location_id'],
                'external_id' => $externalId,
                'xendit_invoice_id' => $invoice['id'] ?? null,
                'status' => $invoice['status'] ?? 'PENDING',
                'currency' => $payload['currency'],
                'amount' => $payload['amount'],
                'payer_email' => $payload['payer_email'],
                'description' => $payload['description'],
                'invoice_url' => $invoice['invoice_url'] ?? null,
                'metadata' => $payload['metadata'],
            ]);

            if (! $request->expectsJson()) {
                $locationId = (string) $validated['location_id'];

                return redirect()->route('ghl.dashboard', ['locationId' => $locationId]);
            }

            return response()->json([
                'ok' => true,
                'payment_id' => $payment->id,
                'external_id' => $payment->external_id,
                'xendit_invoice_id' => $payment->xendit_invoice_id,
                'status' => $payment->status,
                'invoice_url' => $payment->invoice_url,
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
}

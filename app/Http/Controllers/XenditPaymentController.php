<?php

namespace App\Http\Controllers;

use App\Models\XenditPayment;
use App\Services\XenditService;
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
        return view('xendit.test', [
            'payments' => XenditPayment::query()->latest()->limit(20)->get(),
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
                if ((string) $request->input('source') === 'xendit-test-page') {
                    return redirect()->route('xendit.test')->with('success', 'Invoice created successfully.');
                }

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

    public function simulateWebhook(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'invoice_url' => ['nullable', 'url', 'max:500'],
        ]);

        $invoiceId = (string) ($validated['invoice_id'] ?? '');
        $externalId = (string) ($validated['external_id'] ?? '');

        if ($invoiceId === '' && $externalId === '') {
            return redirect()->route('xendit.test')->withErrors([
                'simulate' => 'Provide invoice_id or external_id.',
            ]);
        }

        $query = XenditPayment::query();

        if ($invoiceId !== '') {
            $query->where('xendit_invoice_id', $invoiceId);
        } else {
            $query->where('external_id', $externalId);
        }

        $payment = $query->first();

        if (! $payment) {
            return redirect()->route('xendit.test')->withErrors([
                'simulate' => 'Payment record not found.',
            ]);
        }

        $payload = [
            'id' => $invoiceId !== '' ? $invoiceId : $payment->xendit_invoice_id,
            'external_id' => $externalId !== '' ? $externalId : $payment->external_id,
            'status' => strtoupper($validated['status']),
            'invoice_url' => $validated['invoice_url'] ?? $payment->invoice_url,
        ];

        $payment->update([
            'status' => (string) $payload['status'],
            'invoice_url' => (string) ($payload['invoice_url'] ?? $payment->invoice_url),
            'last_webhook_payload' => $payload,
        ]);

        return redirect()->route('xendit.test')->with('success', 'Webhook simulation applied.');
    }
}

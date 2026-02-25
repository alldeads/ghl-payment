<?php

namespace App\Http\Controllers;

use App\Models\XenditPayment;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class XenditWebhookController extends Controller
{
    public function __construct(private readonly XenditService $xenditService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $callbackToken = $request->header('X-CALLBACK-TOKEN');

        // if (! $this->xenditService->isValidCallbackToken($callbackToken)) {
        //     return response()->json([
        //         'ok' => false,
        //         'message' => 'Invalid callback token.',
        //     ], 401);
        // }

        $invoiceId = (string) Arr::get($request->all(), 'id', '');
        $externalId = (string) Arr::get($request->all(), 'external_id', '');
        $status = (string) Arr::get($request->all(), 'status', 'PENDING');

        if ($invoiceId === '' && $externalId === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Missing invoice reference.',
            ], 422);
        }

        $paymentQuery = XenditPayment::query();

        if ($invoiceId !== '') {
            $paymentQuery->where('xendit_invoice_id', $invoiceId);
        } else {
            $paymentQuery->where('external_id', $externalId);
        }

        $payment = $paymentQuery->first();

        if (! $payment) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment record not found.',
            ], 404);
        }

        $payment->update([
            'status' => strtoupper($status),
            'last_webhook_payload' => $request->all(),
            'invoice_url' => Arr::get($request->all(), 'invoice_url', $payment->invoice_url),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Webhook processed.',
            'payment_id' => $payment->id,
            'status' => $payment->status,
        ], 202);
    }
}

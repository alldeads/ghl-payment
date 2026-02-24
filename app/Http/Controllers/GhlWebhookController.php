<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GhlWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->isValidSignature($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        Log::info('GHL webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Webhook accepted.',
        ], 202);
    }

    private function isValidSignature(Request $request): bool
    {
        $secret = (string) config('services.ghl.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $signature = $this->extractSignature($request);

        if (! $signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computedSignature, $signature);
    }

    private function extractSignature(Request $request): ?string
    {
        $signature = $request->header('X-Webhook-Signature')
            ?: $request->header('X-Wh-Signature')
            ?: $request->header('X-GHL-Signature');

        if (! is_string($signature) || $signature === '') {
            return null;
        }

        return str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;
    }
}

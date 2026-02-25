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
        return response()->json([
            'ok' => true,
            'message' => 'Webhook processed.',
        ], 202);
    }
}

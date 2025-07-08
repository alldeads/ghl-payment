<?php

namespace App\Http\Controllers;

use App\Service\PaymongoService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public $service;

    public function __construct(PaymongoService $service)
    {
        $this->service = $service;
    }

    public function getPaymentMethods(Request $request)
    {
        return $this->response(
            $this->service->getPaymentMethods(),
            'Payment methods retrieved successfully.',
        );
    }

    public function checkoutSession(Request $request)
    {
        $data = $this->service->checkoutSession();
        return $this->response(
            $data,
            'Checkout session created successfully.',
        );
    }
}

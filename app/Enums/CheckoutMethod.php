<?php

namespace App\Enums;

enum CheckoutMethod: string
{
    case ONE_TIME_PAYMENT = 'ONE_TIME_PAYMENT';
    case RECURRING_PAYMENT = 'RECURRING_PAYMENT';
}

<?php

namespace App\Services;

use App\Models\PaymentModes;

class PaymentModeService
{
    public static function list()
    {
        return PaymentModes::all();
    }
}
<?php

namespace App\Services;

use App\Models\Business;
use App\Models\UserBusiness;
use Illuminate\Support\Facades\Auth;

class BusinessService
{
    public static function list() {
        return Business::where('Status','Y')->get();
    }

    public static function userBusiness()
    {
        return UserBusiness::select('Business.Business as Business','Business.BusinessName as BusinessName')->join('Business','Business.Business','UserBusiness.BusinessID')->where('StaffID',Auth::user()->StaffID)->first();
    }
}
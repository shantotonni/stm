<?php

namespace App\Services;

use App\Models\GLCode;
use Illuminate\Support\Facades\DB;

class GLService
{
    public static function voucherTypes()
    {
        return GLCode::where('Category','Voucher')->where('Active','Y')->select(DB::raw("CONCAT(HeadName,'-',AccountCode,'-',NameofLedger) as HeadName"),'GLCodeID','Business')->get();
    }

    public static function advanceTypes()
    {
        return GLCode::where('Category','Advance')->where('Active','Y')->select(DB::raw("CONCAT(HeadName,'-',AccountCode,'-',NameofLedger) as HeadName"),'GLCodeID','Business')->get();
    }
}
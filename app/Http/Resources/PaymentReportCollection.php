<?php

namespace App\Http\Resources;

use App\Models\Currency;
use App\Models\StudentBillPaymentDetails;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentReportCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($bill){
                $currency = Currency::where('name',$bill->currency)->first();
                $total_amount_bdt = StudentBillPaymentDetails::where('roll_no',$bill->roll_no)->where('batch_number',$bill->batch_number)->where('session',$bill->session)->sum('amount_bdt');
                $total_amount_usd = StudentBillPaymentDetails::where('roll_no',$bill->roll_no)->where('batch_number',$bill->batch_number)->where('session',$bill->session)->sum('amount_usd');
                return [
                    'id'=>$bill->id,
                    'student_bill_id'=>$bill->student_bill_id,
                    'student_id'=>$bill->student_id,
                    'category_id'=>$bill->category_id,
                    'student_category'=>isset($bill->category) ? $bill->category->name : '',
                    'name'=>$bill->name,
                    'pay_from_bank_name'=>$bill->pay_from_bank_name,
                    'pay_from_account_no'=>$bill->pay_from_account_no,
                    'roll_no'=>$bill->roll_no,
                    'batch_number'=>$bill->batch_number,
                    'session_id'=>$bill->session_id,
                    'session'=>$bill->session,
                    'payment_date'=>date('Y-m-d',strtotime($bill->payment_date)),
                    'po_do_no'=>$bill->po_do_no,
                    'bank'=>isset($bill->bank) ? $bill->bank->name: '',
                    'branch'=>isset($bill->branch) ? $bill->branch->name: '',
                    'po_date'=>date('Y-m-d',strtotime($bill->po_date)),
                    'account_no'=>$bill->account_no,
                    'symbol'=>$currency->symbol,
                    'currency'=>$bill->currency,
                    'remarks'=>$bill->remarks,
                    'amount_bdt'=>$total_amount_bdt,
                    'amount_usd'=>$total_amount_usd,
                    'late_fee'=>$bill->late_fee,
                    'late_fee_percentage'=>$bill->late_fee_percentage,
                    'late_fee_amount'=>$bill->late_fee_amount,
                    'status'=>$bill->status,
                    'dddd'=>$bill->details,
                ];
            })
        ];
    }
}

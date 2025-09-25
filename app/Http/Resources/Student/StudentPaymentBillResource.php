<?php

namespace App\Http\Resources\Student;

use App\Models\Currency;
use Illuminate\Http\Resources\Json\JsonResource;
use NumberToWords\NumberToWords;

class StudentPaymentBillResource extends JsonResource
{

    public function toArray($request)
    {
        $currency = Currency::where('name',$this->currency)->first();
        $total_amount = 0;
        foreach ($this->details as $amount){
            $total_amount = $total_amount + $amount->amount_bdt;
        }

        return [
            'id'=>$this->id,
            'mro_no'=>$this->id,
            'student_id'=>$this->student_id,
            'category_id'=>$this->category_id,
            'student_category'=>isset($this->category) ? $this->category->name : '',
            'payment_date'=>date('d-m-Y',strtotime($this->payment_date)),
            'head_id'=>$this->head_id,
            'payment_head'=>$this->payment_head,
            'session'=>$this->session,
            'session_id'=>$this->session_id,
            'roll_no'=>$this->roll_no,
            'batch_number'=>$this->batch_number,
            'name'=>$this->name,
            'pay_from_bank_name'=>$this->pay_from_bank_name,
            'pay_from_account_no'=>$this->pay_from_account_no,
            'bank_id'=>$this->bank_id,
            'bank'=>isset($this->bank) ? $this->bank->name: '',
            'branch_id'=>$this->branch_id,
            'branch'=>isset($this->branch) ? $this->branch->name: '',
            'po_do_no'=>$this->po_do_no,
            'po_date'=>date('d-m-Y',strtotime($this->po_date)),
            'account_no'=>$this->account_no,
            'currency'=>$this->currency,
            'remarks'=>$this->remarks,
            'symbol'=>isset($currency->symbol) ? $currency->symbol: '',
            'amount'=>'',
            'amount_in_word'=>numberToWord($total_amount),
            'late_fee'=>$this->late_fee,
            'late_fee_percentage'=>$this->late_fee_percentage,
            'late_fee_amount'=>$this->late_fee_amount,
            'status'=>$this->status,
            'finds'=>$this->details,
            'discount'=>$this->discount,
        ];
    }

}

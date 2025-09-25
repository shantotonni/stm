<?php

namespace App\Http\Resources\HostelFee;

use Illuminate\Http\Resources\Json\JsonResource;

class HostelFeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'title'=>$this->title,
            'mro_no'=>$this->mro_no,
            'studentId'=>$this->student_id,
            'received_from'=>$this->received_from,
            'category'=>$this->category,
            'session_id'=>isset($this->session_id) ? $this->session_id : '',
            'session'=>$this->session,
            'from_month'=>date('d-m-Y',strtotime($this->from_month)),
            'to_month'=>date('d-m-Y',strtotime($this->to_month)),
            'year'=>date('d-m-Y'),
            'total_months'=>$this->total_months,
            'currency'=>$this->currency,
            'roll_no'=>$this->roll_no,
            'batch_number'=>$this->batch_number,
            'id_card'=>$this->id_card,
            'room_no'=>$this->room_no,
            'seat_no'=>$this->seat_no,
            'bank_id'=>$this->bank_id,
            'bank_name'=>$this->bank_name,
            'branch_id'=>$this->branch_id,
            'branch_name'=>$this->branch_name,
            'po_do_no'=>$this->po_do_no,
            'po_date'=>date('d-m-Y',strtotime($this->po_date)),
            'total_amount'=>$this->total_amount,
            'pay_from_bank_name'=>$this->pay_from_bank_name,
            'pay_from_account_no'=>$this->pay_from_account_no,
            'payment_date'=>date('d-m-Y',strtotime($this->created_at)),
            'amount_in_word' => numberToWord($this->total_amount),
        ];
    }


}

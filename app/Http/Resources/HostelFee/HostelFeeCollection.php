<?php

namespace App\Http\Resources\HostelFee;

use App\Models\Sessions;
use App\Models\Student;
use Illuminate\Http\Resources\Json\ResourceCollection;

class HostelFeeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($hostel_fee){
                $session = Sessions::where('name',$hostel_fee->session)->first();
                return [
                    'id'=>$hostel_fee->id,
                    'mro_no'=>$hostel_fee->mro_no,
                    'title'=>$hostel_fee->title,
                    'studentId'=>$hostel_fee->student_id,
                    'student'=>Student::where('student_id',$hostel_fee->student_id)->first(),
                    'received_from'=>$hostel_fee->received_from,
                    'category'=>$hostel_fee->category,
                    'session_id'=>isset($session->session_id) ? $session->session_id : '',
                    'session'=>$hostel_fee->session,
                    'from_month'=>date('d-m-Y',strtotime($hostel_fee->from_month)),
                    'to_month'=>date('d-m-Y',strtotime($hostel_fee->to_month)),
                    'total_months'=>$hostel_fee->total_months,
                    'year'=>date('d-m-Y',strtotime($hostel_fee->year)),
                    'roll_no'=>$hostel_fee->roll_no,
                    'batch_number'=>$hostel_fee->batch_number,
                    'id_card'=>$hostel_fee->id_card,
                    'room_no'=>$hostel_fee->room_no,
                    'seat_no'=>$hostel_fee->seat_no,
                    'bank_id'=>$hostel_fee->bank_id,
                    'bank_name'=>$hostel_fee->bank_name,
                    'branch_id'=>$hostel_fee->branch_id,
                    'branch_name'=>$hostel_fee->branch_name,
                    'po_do_no'=>$hostel_fee->po_do_no,
                    'po_date'=>date('d-m-Y',strtotime($hostel_fee->po_date)),
                    'total_amount'=>$hostel_fee->total_amount,
                    'currency'=>$hostel_fee->currency,
                    'pay_from_bank_name'=>$hostel_fee->pay_from_bank_name,
                    'pay_from_account_no'=>$hostel_fee->pay_from_account_no,
                    'account_no'=>$hostel_fee->account_no,
                    'remarks'=>$hostel_fee->remarks,
                    'amount_in_word' => numberToWord($hostel_fee->total_amount),
                ];
            })
        ];
    }
}

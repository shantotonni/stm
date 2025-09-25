<?php

namespace App\Http\Resources\Student;

use App\Models\Student;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentBillCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($student_bill){
                if ($student_bill->due_amount == 0){
                    $status = 'Completed';
                }else{
                    $status = 'Pending';
                }

                return [
                    'student_bill_id'=>$student_bill->student_bill_id,
                    'session_id'=>isset($student_bill->student) ? $student_bill->student->session_id:'',
                    'student_id'=>$student_bill->student_id,
                    'roll_no'=>isset($student_bill->student) ? $student_bill->student->roll_no:'',
                    'student_name'=>isset($student_bill->student) ? $student_bill->student->first_name.' '. $student_bill->student->last_name: '' ,
                    'student_roll'=>isset($student_bill->student) ? $student_bill->student->roll_no: '' ,
                    'payment_head'=>$student_bill->payment_head,
                    'expected_payment_date'=>date('d-m-Y',strtotime($student_bill->expected_payment_date)),
                    'amount'=>$student_bill->amount,
                    'paid_amount'=>$student_bill->paid_amount,
                    'due_amount'=>$student_bill->due_amount,
                    'ordering'=>$student_bill->ordering,
                    'currency'=>isset($student_bill->student->category) ? $student_bill->student->category->currency->name: '',
                    'currency_symbol'=>isset($student_bill->student->category) ? $student_bill->student->category->currency->symbol: '',
                    'status'=>$status,
                    'session'=>isset($student_bill->student->session) ? $student_bill->student->session->name:'',
                    'student_batch'=>isset($student_bill->student) ? $student_bill->student->batch_number:'',
                    'category'=>isset($student_bill->student->category) ? $student_bill->student->category->name:'',
                ];
            })
        ];
    }
}

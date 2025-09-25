<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentPaidDueReportCollection extends ResourceCollection
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
            'data'=>$this->collection->transform(function ($student_bill){

                return [
                    'session_id'=>isset($student_bill->student) ? $student_bill->student->session_id:'',
                    'student_id'=>$student_bill->student_id,
                    'roll_no'=>isset($student_bill->student) ? $student_bill->student->roll_no:'',
                    'student_name'=>isset($student_bill->student) ? $student_bill->student->first_name.' '. $student_bill->student->last_name: '' ,
                    'student_roll'=>isset($student_bill->student) ? $student_bill->student->roll_no: '' ,
                    'total_amount'=>$student_bill->total_amount,
                    'paid_amount'=>$student_bill->paid_amount,
                    'due_amount'=>$student_bill->due_amount,
                    'session'=>isset($student_bill->student->session) ? $student_bill->student->session->name:'',
                    'student_batch'=>isset($student_bill->student) ? $student_bill->student->batch_number:'',
                    'category'=>isset($student_bill->student->category) ? $student_bill->student->category->name:'',
                    'currency'=>isset($student_bill->student->category) ? $student_bill->student->category->currency->name: '',
                    'currency_symbol'=>isset($student_bill->student->category) ? $student_bill->student->category->currency->symbol: '',
                ];
            })
        ];
    }
}

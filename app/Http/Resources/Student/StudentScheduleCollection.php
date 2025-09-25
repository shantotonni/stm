<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentScheduleCollection extends ResourceCollection
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
            'data'=>$this->collection->transform(function ($student_schdule){
                return [
                    'student_bill_id'=>$student_schdule->student_bill_id,
                    'student_id'=>$student_schdule->student_id,
                    'student_name'=>isset($student_schdule->student) ? $student_schdule->student->name:'',
                    'session'=>isset($student_schdule->student->session) ? $student_schdule->student->session->name: '',
                    'expected_payment_date'=> $student_schdule->expected_payment_date,
                    'year'=>$student_schdule->payment_head,
                    'category'=>isset($student_schdule->student->category) ? $student_schdule->student->category->name: '',
                    'amount'=>$student_schdule->amount,
                    'currency'=>isset($student_schdule->student->category) ? $student_schdule->student->category->currency->name: '',
                    'currency_symbol'=>isset($student_schdule->student->category) ? $student_schdule->student->category->currency->symbol:'',
                    'ordering'=>$student_schdule->ordering,
                ];
            })
        ];
    }
}

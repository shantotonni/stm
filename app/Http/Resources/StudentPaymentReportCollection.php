<?php

namespace App\Http\Resources;

use App\Models\Currency;
use App\Models\Student;
use App\Models\StudentBillPayment;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentPaymentReportCollection extends ResourceCollection
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
                $student_bill_payment = StudentBillPayment::query()->where('roll_no',$bill->roll_no)->where('batch_number',$bill->batch_number)->first();
                $currency = Currency::where('name',$student_bill_payment->currency)->first();
                //$student = Student::where('roll_no',$bill->roll_no)->where('batch_number',$bill->batch_number)->first();
                return [
                    'id'=>$bill->id,
                    'student_bill_id'=>$bill->student_bill_id,
                    'student_id'=>$bill->student_id,
                    'category_id'=>$bill->category_id,
                    'student_category'=>isset($bill->category) ? $bill->category->name : '',
                    'name'=>$student_bill_payment->name,
                    'roll_no'=>$bill->roll_no,
                    'batch_number'=>$bill->batch_number,
                    'session_id'=>$student_bill_payment->session_id,
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
                    'amount'=>$bill->amount,
                    'late_fee'=>$bill->late_fee,
                    'late_fee_percentage'=>$bill->late_fee_percentage,
                    'late_fee_amount'=>$bill->late_fee_amount,
                    'status'=>$bill->status,
                ];
            })
        ];

//        return [
//            'data'=>$this->collection->transform(function ($bill){
//                $student_bill_payment = StudentBillPayment::query()->where('roll_no',$bill->roll_no)->where('batch_number',$bill->batch_number)->first();
//                $currency = Currency::where('name',$student_bill_payment->currency)->first();
//                $student = Student::where('roll_no',$bill->roll_no)->where('batch_number',$bill->batch_number)->first();
//                return [
//                    'student_bill_payment_id'=>$bill->student_bill_payment_id,
//                    'amount_bdt'=>$bill->amount_bdt,
//                    'amount_usd'=>$bill->amount_usd,
//                    'student_id'=>$student->student_id,
//                    'category_id'=>$student->category_id,
//                    'student_category'=>isset($student->category) ? $student->category->name : '',
//                    'name'=>$student->first_name.' '.$student->last_name,
//                    'roll_no'=>$bill->roll_no,
//                    'batch_number'=>$bill->batch_number,
//                    'session_id'=>$student->session_id,
//                    'session'=>$bill->session,
//                    'symbol'=>$currency->symbol,
//                    'currency'=>$student_bill_payment->currency,
//                    'payment_date'=>$this->payment_date,
//                ];
//            })
//        ];
    }
}

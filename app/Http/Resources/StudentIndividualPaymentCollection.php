<?php

namespace App\Http\Resources;

use App\Http\Resources\Student\StudentBillCollection;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentBillPayment;
use App\Models\Year;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentIndividualPaymentCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($individual){
                $student_bill_payment = StudentBillPayment::query()->with(['bank','branch',])->where('roll_no',$individual->roll_no)->where('batch_number',$individual->batch_number)->first();
                $student = Student::with('category')->where('roll_no',$individual->roll_no)->where('batch_number',$individual->batch_number)->first();
                $student_bill = StudentBill::where('student_id',$student->student_id)->where('payment_head',$individual->payment_head)->first();
                if ($student_bill_payment->currency == 'BDT'){
                    $due_bdt = $student_bill->amount - $individual->amount_bdt;
                    $due_usd = 0;
                }else{
                    $due_usd = $student_bill->amount - $individual->amount_usd;
                    $due_bdt = 0;
                }

                return [
                    'student_name'=>$student->first_name.' '.$student->last_name,
                    'student_category'=>isset($student->category) ? $student->category->name: '',
                    'payment_head'=>$individual->payment_head,
                    'roll_no'=>$individual->roll_no,
                    'session'=>$individual->session,
                    'batch_number'=>$individual->batch_number,

                    'total_amount'=>$student_bill->amount,
                    'paid_amount_bdt'=>$individual->amount_bdt,
                    'due_amount_bdt'=> $due_bdt,
                    'paid_amount_usd'=>$individual->amount_usd,
                    'due_amount_usd'=>$due_usd,

                    'payment_date'=>date('d-m-Y',strtotime($student_bill_payment->payment_date)),
                    'po_do_no'=>$student_bill_payment->po_do_no,
                    'bank'=>isset($student_bill_payment->bank) ? $student_bill_payment->bank->name: '',
                    'branch'=>isset($student_bill_payment->branch) ? $student_bill_payment->branch->name: '',
                    'po_date'=>date('d-m-Y',strtotime($student_bill_payment->po_date)),
                    'account_no'=>$student_bill_payment->account_no,
                    'symbol'=>$student_bill_payment->symbol,
                    'remarks'=>$student_bill_payment->remarks,
                ];
            })
        ];
    }
}

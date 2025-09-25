<?php

namespace App\Exports\Student;

use App\Http\Resources\Student\StudentPaymentBillCollection;
use App\Models\StudentBillPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentPaymentBillExport implements FromCollection, WithHeadings
{
    private $request;

    public function __construct($Data)
    {
        $this->request = $Data;
    }

    public function collection()
    {
        $request = $this->request;
        $studentId = $request->student_id;
        $roll_number = $request->roll_number;

        $students = StudentBillPayment::with('student','bank','branch')->get();
//        if (!empty($studentId)){
//            $students = $students->where('student_id',$studentId);
//        }
//        if (!empty($roll_number)){
//            $students = $students->where('roll_no',$roll_number);
//        }

       // $students = $students->get();
        return new StudentPaymentBillCollection($students);
    }

    public function headings(): array
    {

        return [
            'id',
            'student_bill_id',
            'student_id',
            'name',
            'roll_no',
            'session',
            'payment_head',
            'payment_date',
            'po_do_no',
            'bank',
            'branch',
            'po_date',
            'account_no',
            'currency',
            'amount',
            'status',
        ];
    }
}

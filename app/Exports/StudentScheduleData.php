<?php

namespace App\Exports;

use App\Http\Resources\Student\StudentScheduleCollection;
use App\Models\StudentBill;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentScheduleData implements FromCollection, WithHeadings
{
    private $request;

    public function __construct($Data)
    {
        $this->request = $Data;
    }

    public function collection()
    {
        $request = $this->request;

        $data = $request->all();
        $search = $data['query'];
        $student_id = $request->student_id;

        $students = StudentBill::query()->orderBy('ordering','asc')->with('student','student.session');
        if (!empty($student_id)){
            $students = $students->where('student_id',$student_id);
        }

        $students = $students->where(function ($query) use($search){
            if (!empty($search)){
                $query->where('payment_head','LIKE',"%$search%");
            }
        })->get();

        return $students;
    }


    public function headings(): array
    {
        return [
            'student_bill_id',
            'student_id',
            'expected_payment_date',
            'payment_head',
            'amount',
            'paid_amount',
            'due_amount',
            'amount',
        ];
    }
}

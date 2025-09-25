<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentCollection extends ResourceCollection
{

    public function toArray($request)
    {
        return [
            'data'=>$this->collection->transform(function ($student){
                return [
                    'student_id'=>$student->student_id,
                    'name'=>$student->name,
                    'student_id_number'=>$student->student_id_number,
                    'roll_no'=>$student->roll_no,
                    'email'=>$student->email,
                    'mobile'=>$student->mobile,
                    'session'=>$student->session,
                    'status'=>$student->status,
                    'is_change_password'=>$student->is_change_password,
                ];
            })
        ];
    }
}

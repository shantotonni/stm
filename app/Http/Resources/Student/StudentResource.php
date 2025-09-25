<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'student_id'=>$this->student_id,
            'name'=>$this->name,
            'roll_no'=>$this->roll_no,
            'email'=>$this->email,
            'mobile'=>$this->mobile,
            'session_id'=>$this->session_id,
            'session_name'=>isset($this->session) ? $this->session->name:'',
            'status'=>$this->status,
            'is_change_password'=>$this->is_change_password,
        ];
    }
}

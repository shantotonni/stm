<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'session_id'=>'required',
            'student_name'=>'required',
            'payment_head'=>'required',
            'amount'=>'required',
            //'paid_amount'=>'required',
            //'due_amount'=>'required',
            'expected_payment_date'=>'required',
            'ordering'=>'required',
        ];
    }
}

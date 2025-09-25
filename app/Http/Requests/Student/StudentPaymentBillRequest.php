<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentPaymentBillRequest extends FormRequest
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
            'student_id'=>'required',
            //'head_id'=>'required',
            'session_id'=>'required',
            'roll_no'=>'required',
            //'pay_from_bank_name'=>'required',
            'bank_id'=>'required',
            'branch_id'=>'required',
            //'po_do_no'=>'required',
            //'po_date'=>'required',
            'account_no'=>'required',
            'currency'=>'required',
            //'amount'=>'required',
            //'late_fee'=>'required',
        ];
    }
}

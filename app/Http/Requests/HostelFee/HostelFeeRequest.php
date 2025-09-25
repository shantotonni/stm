<?php

namespace App\Http\Requests\HostelFee;

use Illuminate\Foundation\Http\FormRequest;

class HostelFeeRequest extends FormRequest
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
            'received_from'=>'required|min:3',
            'batch_number'=>'required',
            'roll_no'=>'required',
            //'student_id'=>'required',
            'session_id'=>'required',
            'category'=>'required',
        ];
    }
}

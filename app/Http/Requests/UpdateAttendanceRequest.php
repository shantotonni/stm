<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'attendance_status' => 'required|in:present,absent,late,excused',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_status.required' => 'Attendance status is required',
            'attendance_status.in' => 'Invalid attendance status. Must be present, absent, late, or excused',
            'remarks.max' => 'Remarks cannot exceed 500 characters',
        ];
    }
}

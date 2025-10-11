<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAttendanceRequest extends FormRequest
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
            'class_id' => 'required|exists:classes,id',
            'attendance' => 'required|array|min:1',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.attendance_status' => 'required|in:present,absent,late,excused',
            'attendance.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'class_id.required' => 'Class ID is required',
            'class_id.exists' => 'Selected class does not exist',
            'attendance.required' => 'Attendance data is required',
            'attendance.*.student_id.required' => 'Student ID is required for each attendance record',
            'attendance.*.student_id.exists' => 'One or more students do not exist',
            'attendance.*.attendance_status.required' => 'Attendance status is required',
            'attendance.*.attendance_status.in' => 'Invalid attendance status. Must be present, absent, late, or excused',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate student IDs in the request
            $studentIds = collect($this->attendance)->pluck('student_id');
            if ($studentIds->count() !== $studentIds->unique()->count()) {
                $validator->errors()->add('attendance', 'Duplicate student entries found');
            }
        });
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'student' => [
                'id' => $this->student->id ?? null,
                'roll_number' => $this->student->roll_number ?? null,
                'full_name' => $this->student->full_name ?? null,
                'photo_url' => $this->student->photo_url ?? null,
                'department' => $this->student->department->name ?? null,
            ],
            'class' => [
                'id' => $this->class->id ?? null,
                'topic' => $this->class->topic ?? null,
                'class_date' => $this->class->class_date->format('Y-m-d'),
                'subject' => $this->class->schedule->subject->name ?? null,
            ],
            'attendance_status' => $this->attendance_status,
            'attendance_status_label' => ucfirst($this->attendance_status),
            'remarks' => $this->remarks,
            'marked_at' => $this->marked_at?->format('Y-m-d H:i:s'),
            'marked_by' => [
                'id' => $this->markedBy->id ?? null,
                'name' => $this->markedBy->name ?? null,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

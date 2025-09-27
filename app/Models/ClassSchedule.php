<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'teacher_id', 'classroom_id', 'session_id',
        'day_of_week', 'start_time', 'end_time', 'class_type', 'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function session()
    {
        return $this->belongsTo(Sessions::class, 'session_id');
    }

    public function classes()
    {
        return $this->hasMany(Classes::class, 'schedule_id');
    }
}

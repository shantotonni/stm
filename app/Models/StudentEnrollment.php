<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'program_id', 'session_id', 'subject_id', 'enrollment_date', 'is_active'
    ];


    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function program() {
        return $this->belongsTo(Program::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function session()
    {
        return $this->belongsTo(Sessions::class, 'session_id');
    }

}

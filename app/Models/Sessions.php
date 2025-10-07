<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sessions extends Model
{
    use HasFactory;
    protected $table = "sessions";
    public $primaryKey = 'id';
    protected $fillable = ['name', 'from_period', 'to_period','batch_number', 'is_current', 'is_active'];

    protected $casts = [
        'from_period' => 'date',
        'to_period' => 'date',
        'is_current' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'session_id');
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'session_id');
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'session_id');
    }
}

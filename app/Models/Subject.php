<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';
    public $primaryKey = 'id';
    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects')
            ->withPivot('session_id', 'is_coordinator')
            ->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_enrollments')
            ->withPivot('enrollment_date', 'is_active')
            ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function materials()
    {
        return $this->hasMany(ClassMaterial::class);
    }

}

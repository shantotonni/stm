<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';
    public $primaryKey = 'id';
    protected $fillable = [
        'name',
        'code',
        'department_id',
        'program_id',
        'year',
        'semester',
        'credit_hours',
        'theory_hours',
        'practical_hours',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_hours' => 'integer',
        'theory_hours' => 'integer',
        'practical_hours' => 'integer',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';
    public $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'BMDC_NO',
        'name',
        'email',
        'mobile',
        'department_id',
        'designation_id',
        'qualification',
        'joining_date',
        'is_head',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getIsHeadAttribute($value)
    {
        return $value === 'Y';
    }

    public function setIsHeadAttribute($value)
    {
        $this->attributes['is_head'] = $value ? 'Y' : 'N';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')
            ->withPivot('session_id', 'is_coordinator','academic_year', 'is_primary')
            ->withTimestamps();
    }

    public function teacherSubjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function subjectsForSession($sessionId)
    {
        return $this->subjects()
            ->wherePivot('session_id', $sessionId)
            ->get();
    }

    public function supervisedExams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_supervisors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function subjectsForYear($year)
    {
        return $this->subjects()
            ->wherePivot('academic_year', $year)
            ->get();
    }

    public function coordinatorSubjects()
    {
        return $this->subjects()
            ->wherePivot('is_coordinator', 1)
            ->get();
    }

    public function primarySubjects()
    {
        return $this->subjects()
            ->wherePivot('is_primary', 1)
            ->get();
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function classes()
    {
        return $this->hasMany(Classes::class, 'created_by');
    }

    public function evaluations()
    {
        return $this->hasMany(TeacherEvaluation::class);
    }

    public function materials()
    {
        return $this->hasMany(ClassMaterial::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

}

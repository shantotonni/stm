<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'exam_type_id',
        'teacher_id',
        'session_id',
        'department_id',
        'classroom_id',
        'title',
        'exam_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'total_marks',
        'pass_marks',
        'semester',
        'year',
        'instructions',
        'status',
        'syllabus_topics',
        'created_by'
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function session()
    {
        return $this->belongsTo(Sessions::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'exam_students')
            ->withPivot('is_eligible', 'attendance_status', 'seat_number')
            ->withTimestamps();
    }

    public function examStudents()
    {
        return $this->hasMany(ExamStudent::class);
    }

    public function studentCount()
    {
        return $this->examStudents()->count();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function publishedResults()
    {
        return $this->results()->published();
    }

    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'exam_supervisors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ExamNotification::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(ExamAttendance::class);
    }

    // Scopes for filtering
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    public function scopeYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('exam_date');
    }

    // Accessors & Mutators
    public function getFormattedExamDateAttribute()
    {
        return $this->exam_date->format('d M, Y');
    }

    public function getFormattedTimeRangeAttribute()
    {
        return $this->start_time->format('h:i A') . ' - ' . $this->end_time->format('h:i A');
    }

    public function getIsUpcomingAttribute()
    {
        return $this->exam_date >= now()->toDateString() && $this->status === 'scheduled';
    }

    public function chiefSupervisors()
    {
        return $this->supervisors()->wherePivot('role', 'chief');
    }

    public function assistantSupervisors()
    {
        return $this->supervisors()->wherePivot('role', 'assistant');
    }

    public function invigilators()
    {
        return $this->supervisors()->wherePivot('role', 'invigilator');
    }

    // Calculate total marks automatically
    public static function boot()
    {
        parent::boot();

        static::creating(function ($result) {
            $result->total_marks = ($result->theory_marks ?? 0) +
                ($result->practical_marks ?? 0) +
                ($result->viva_marks ?? 0);
        });

        static::updating(function ($result) {
            $result->total_marks = ($result->theory_marks ?? 0) +
                ($result->practical_marks ?? 0) +
                ($result->viva_marks ?? 0);
        });
    }

}

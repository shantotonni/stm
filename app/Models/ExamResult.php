<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'marks_obtained',
        'grade',
        'status',
        'percentage',
        'remarks',
        'evaluated_by',
        'published_at'
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'percentage' => 'decimal:2',
        'published_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($result) {
            // Auto-calculate percentage if exam is loaded
            if ($result->exam) {
                $result->percentage = ($result->marks_obtained / $result->exam->total_marks) * 100;

                // Auto-calculate grade
                $result->grade = self::calculateGrade($result->percentage);

                // Auto-determine pass/fail status
                $result->status = $result->marks_obtained >= $result->exam->passing_marks ? 'pass' : 'fail';
            }
        });
    }


    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function calculateGrade()
    {
        $percentage = ($this->marks_obtained / $this->exam->total_marks) * 100;

        if ($percentage >= 80) return 'A+';
        if ($percentage >= 75) return 'A';
        if ($percentage >= 70) return 'A-';
        if ($percentage >= 65) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 55) return 'B-';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 45) return 'C';
        if ($percentage >= 40) return 'D';
        return 'F';
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopePassed($query)
    {
        return $query->where('status', 'pass');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'fail');
    }
}

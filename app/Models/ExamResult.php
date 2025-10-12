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
            if ($result->exam) {
                // Calculate percentage
                $result->percentage = ($result->marks_obtained / $result->exam->total_marks) * 100;

                // Calculate grade (instance method)
                $result->grade = $result->calculateGrade();

                // Determine pass/fail
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

    public function calculateGrade(): string
    {
        $percentage = $this->percentage;

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

    public static function calculateGradeAndGpa($percentage)
    {
        if ($percentage >= 80) {
            return ['grade' => 'A+', 'gpa' => 5.00];
        } elseif ($percentage >= 75) {
            return ['grade' => 'A', 'gpa' => 4.00];
        } elseif ($percentage >= 70) {
            return ['grade' => 'A-', 'gpa' => 3.50];
        } elseif ($percentage >= 65) {
            return ['grade' => 'B+', 'gpa' => 3.25];
        } elseif ($percentage >= 60) {
            return ['grade' => 'B', 'gpa' => 3.00];
        } elseif ($percentage >= 55) {
            return ['grade' => 'B-', 'gpa' => 2.75];
        } elseif ($percentage >= 50) {
            return ['grade' => 'C+', 'gpa' => 2.50];
        } elseif ($percentage >= 45) {
            return ['grade' => 'C', 'gpa' => 2.25];
        } elseif ($percentage >= 40) {
            return ['grade' => 'D', 'gpa' => 2.00];
        } else {
            return ['grade' => 'F', 'gpa' => 0.00];
        }
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

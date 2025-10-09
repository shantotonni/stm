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
        'remarks',
        'evaluated_by',
        'evaluated_at'
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'evaluated_at' => 'datetime'
    ];


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
        return $this->belongsTo(Teacher::class, 'evaluated_by');
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
}

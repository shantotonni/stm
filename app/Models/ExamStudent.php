<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'is_eligible',
        'attendance_status',
        'seat_number'
    ];

    protected $casts = [
        'is_eligible' => 'boolean',
        'created_at' => 'datetime',
    ];

    const UPDATED_AT = null; // No updated_at column

    // Relationships
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Scopes
    public function scopeEligible($query)
    {
        return $query->where('is_eligible', true);
    }

    public function scopePresent($query)
    {
        return $query->where('attendance_status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    // Helper Methods
    public static function generateSeatNumber($examId, $prefix = 'SEAT-')
    {
        $lastSeat = self::where('exam_id', $examId)
            ->whereNotNull('seat_number')
            ->orderBy('seat_number', 'desc')
            ->first();

        if ($lastSeat && preg_match('/(\d+)$/', $lastSeat->seat_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function markPresent()
    {
        $this->update(['attendance_status' => 'present']);
    }

    public function markAbsent()
    {
        $this->update(['attendance_status' => 'absent']);
    }
}

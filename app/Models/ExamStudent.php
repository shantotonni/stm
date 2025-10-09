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
        'seat_number',
    ];

    protected $casts = [
        'is_eligible' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected $with = ['student', 'exam'];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
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

    public function scopeByExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    // Accessor
    public function getAttendanceStatusLabelAttribute()
    {
        return [
                'present' => 'Present',
                'absent' => 'Absent',
                'excused' => 'Excused',
            ][$this->attendance_status] ?? 'N/A';
    }

    // Methods
    public function markPresent()
    {
        return $this->update(['attendance_status' => 'present']);
    }

    public function markAbsent()
    {
        return $this->update(['attendance_status' => 'absent']);
    }

    public function assignSeat($seatNumber)
    {
        return $this->update(['seat_number' => $seatNumber]);
    }

    public static function autoAssignSeats($examId, $startSeatNumber = 1)
    {
        $students = self::where('exam_id', $examId)
            ->eligible()
            ->orderBy('student_id')
            ->get();

        $seatNumber = $startSeatNumber;
        foreach ($students as $student) {
            $student->assignSeat(str_pad($seatNumber, 4, '0', STR_PAD_LEFT));
            $seatNumber++;
        }

        return $students->count();
    }
}

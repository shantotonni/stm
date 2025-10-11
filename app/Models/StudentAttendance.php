<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'student_id', 'attendance_status', 'marked_at', 'marked_by', 'remarks'
    ];

    protected $casts = [
        'marked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('marked_at', [$startDate, $endDate]);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('attendance_status', $status);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function isPresent(): bool
    {
        return in_array($this->attendance_status, ['present', 'late']);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public static function isEligibleForExam($studentId, $subjectId = null): array
    {
        $query = self::query()
            ->join('classes', 'student_attendance.class_id', '=', 'classes.id')
            ->where('student_attendance.student_id', $studentId)
            ->where('classes.status', 'completed');

        if ($subjectId) {
            $query->join('class_schedules', 'classes.schedule_id', '=', 'class_schedules.id')
                ->where('class_schedules.subject_id', $subjectId);
        }

        $totalClasses = $query->count();
        $attendedClasses = (clone $query)
            ->whereIn('student_attendance.attendance_status', ['present', 'late'])
            ->count();

        $percentage = $totalClasses > 0 ? round(($attendedClasses / $totalClasses) * 100, 2) : 0;
        $isEligible = $percentage >= 75;

        return [
            'total_classes' => $totalClasses,
            'attended_classes' => $attendedClasses,
            'percentage' => $percentage,
            'is_eligible' => $isEligible,
            'required_percentage' => 75,
        ];
    }

    public static function getClassSummary($classId): array
    {
        $total = self::where('class_id', $classId)->count();
        $present = self::where('class_id', $classId)->where('attendance_status', 'present')->count();
        $absent = self::where('class_id', $classId)->where('attendance_status', 'absent')->count();
        $late = self::where('class_id', $classId)->where('attendance_status', 'late')->count();
        $excused = self::where('class_id', $classId)->where('attendance_status', 'excused')->count();

        return [
            'total_students' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 2) : 0,
        ];
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('marked_at', today());
    }

    public static function getAttendancePercentage($studentId, $classId, $startDate, $endDate)
    {
        $total = self::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->whereBetween('marked_at', [$startDate, $endDate])
            ->count();

        $present = self::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('attendance_status', 'present')
            ->whereBetween('marked_at', [$startDate, $endDate])
            ->count();

        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }
}

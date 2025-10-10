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

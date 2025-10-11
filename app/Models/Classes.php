<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id', 'class_date', 'actual_start_time', 'actual_end_time',
        'topic', 'description', 'status', 'created_by'
    ];

    protected $casts = [
        'class_date' => 'date',
    ];


    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'class_id');
    }

    public function materials()
    {
        return $this->hasMany(ClassMaterial::class, 'class_id');
    }

    // Scope for filtering by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope for filtering by date
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('class_date', $date);
    }

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            StudentAttendance::class,
            'class_id',
            'id',
            'id',
            'student_id'
        )->distinct();
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('class_date', [$startDate, $endDate]);
    }

    public function hasAttendanceMarked(): bool
    {
        return $this->attendances()->exists();
    }

    public function getAttendanceSummary(): array
    {
        return StudentAttendance::getClassSummary($this->id);
    }

    // Get attendance statistics for this class
    public function getAttendanceStatistics()
    {
        $statistics = $this->attendances()
            ->selectRaw('attendance_status, COUNT(*) as count')
            ->groupBy('attendance_status')
            ->pluck('count', 'attendance_status');

        $total = $statistics->sum();

        return [
            'present' => $statistics->get('present', 0),
            'absent' => $statistics->get('absent', 0),
            'late' => $statistics->get('late', 0),
            'excused' => $statistics->get('excused', 0),
            'total' => $total,
            'present_percentage' => $total > 0 ? round(($statistics->get('present', 0) / $total) * 100, 2) : 0
        ];
    }

}

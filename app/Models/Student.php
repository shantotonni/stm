<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';
    public $primaryKey = 'id';
    protected $fillable = [
        'user_id', 'department_id', 'name', 'student_id_number', 'roll_no',
        'email', 'mobile', 'session', 'session_id', 'category_id','program_id', 'category',
        'year', 'semester', 'is_hostel', 'nationality', 'address', 'nid',
        'date_of_birth', 'batch', 'blood_group', 'guardian_name',
        'guardian_phone', 'emergency_contact', 'admission_date', 'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Y');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('student_id_number', 'like', "%{$search}%")
                ->orWhere('roll_no', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%");
        });
    }

    public function session(){
        return $this->belongsTo(Sessions::class,'session_id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class,'student_id');
    }

    public function materialAccess()
    {
        return $this->hasMany(StudentMaterialAccess::class);
    }

    // Get enrolled subjects for current session
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_enrollments')
            ->withPivot('enrollment_date', 'is_active')
            ->wherePivot('is_active', true);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_students')
            ->withPivot('is_eligible', 'attendance_status', 'seat_number')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(ExamAttendance::class);
    }

    // Calculate attendance percentage for a date range
    public function getAttendancePercentage($startDate = null, $endDate = null)
    {
        $query = $this->attendances();

        if ($startDate && $endDate) {
            $query->whereBetween('marked_at', [$startDate, $endDate]);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0;
        }

        $present = $query->whereIn('attendance_status', ['present', 'late'])->count();

        return round(($present / $total) * 100, 2);
    }

    // Get attendance summary
    public function getAttendanceSummary($startDate = null, $endDate = null)
    {
        $query = $this->attendances();

        if ($startDate && $endDate) {
            $query->whereBetween('marked_at', [$startDate, $endDate]);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_classes,
            SUM(CASE WHEN attendance_status = "present" THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN attendance_status = "absent" THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN attendance_status = "late" THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN attendance_status = "excused" THEN 1 ELSE 0 END) as excused
        ')->first();

        return [
            'total_classes' => $summary->total_classes ?? 0,
            'present' => $summary->present ?? 0,
            'absent' => $summary->absent ?? 0,
            'late' => $summary->late ?? 0,
            'excused' => $summary->excused ?? 0,
            'attendance_percentage' => $this->getAttendancePercentage($startDate, $endDate)
        ];
    }

    /**
     * Calculate attendance percentage for a specific subject or overall
     */
    public function calculateAttendancePercentage($subjectId = null, $startDate = null, $endDate = null): array
    {
        $query = $this->attendances()
            ->join('classes', 'student_attendances.class_id', '=', 'classes.id')
            ->where('classes.status', 'completed');

        if ($subjectId) {
            $query->join('class_schedules', 'classes.schedule_id', '=', 'class_schedules.id')
                ->where('class_schedules.subject_id', $subjectId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('classes.class_date', [$startDate, $endDate]);
        }

        $totalClasses = $query->count();
        $attendedClasses = (clone $query)
            ->whereIn('student_attendances.attendance_status', ['present', 'late'])
            ->count();

        $percentage = $totalClasses > 0 ? round(($attendedClasses / $totalClasses) * 100, 2) : 0;

        return [
            'student_id' => $this->id,
            'student_name' => $this->full_name,
            'roll_number' => $this->roll_number,
            'total_classes' => $totalClasses,
            'attended_classes' => $attendedClasses,
            'absent_classes' => $totalClasses - $attendedClasses,
            'percentage' => $percentage,
            'is_eligible' => $percentage >= 75,
            'status' => $percentage >= 75 ? 'eligible' : 'not_eligible',
        ];
    }

    public function getMonthlyAttendance($year, $month): array
    {
        $attendances = $this->attendances()
            ->join('classes', 'student_attendance.class_id', '=', 'classes.id')
            ->whereYear('classes.class_date', $year)
            ->whereMonth('classes.class_date', $month)
            ->select('student_attendance.*', 'classes.class_date', 'classes.topic')
            ->orderBy('classes.class_date')
            ->get();

        $summary = [
            'total' => $attendances->count(),
            'present' => $attendances->where('attendance_status', 'present')->count(),
            'absent' => $attendances->where('attendance_status', 'absent')->count(),
            'late' => $attendances->where('attendance_status', 'late')->count(),
            'excused' => $attendances->where('attendance_status', 'excused')->count(),
        ];

        return [
            'attendances' => $attendances,
            'summary' => $summary,
            'percentage' => $summary['total'] > 0
                ? round((($summary['present'] + $summary['late']) / $summary['total']) * 100, 2)
                : 0,
        ];
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }


}

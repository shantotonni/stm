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
        return $this->hasMany(StudentAttendance::class);
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
}

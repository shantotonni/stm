<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';
    public $primaryKey = 'id';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
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
}

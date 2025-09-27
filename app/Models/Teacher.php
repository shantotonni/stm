<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';
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

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')
            ->withPivot('session_id', 'is_coordinator')
            ->withTimestamps();
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function classes()
    {
        return $this->hasMany(Classes::class, 'created_by');
    }

    public function evaluations()
    {
        return $this->hasMany(TeacherEvaluation::class);
    }

    public function materials()
    {
        return $this->hasMany(ClassMaterial::class);
    }

}

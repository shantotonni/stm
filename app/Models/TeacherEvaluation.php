<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherEvaluation extends Model
{
    use HasFactory;

    protected $table = 'teacher_evaluations';
    public $primaryKey = 'id';

    protected $fillable = [
        'student_name',
        'teacher_id',
        'student_phase',
        'is_role_model',
    ];

    public function details()
    {
        return $this->hasMany(TeacherEvaluationDetails::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class,'teacher_id','user_id');
    }

}

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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function evaluations()
    {
        return $this->hasMany(TeacherEvaluation::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}

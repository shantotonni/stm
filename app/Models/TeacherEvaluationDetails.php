<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherEvaluationDetails extends Model
{
    use HasFactory;

    protected $table = 'teacher_evaluations_details';
    public $primaryKey = 'id';

    protected $fillable = [
        'teacher_evaluation_id',
        'evaluation_statement_id',
        'rating'
    ];

    public function getRatingTextAttribute()
    {
        if ($this->statement->type === 'boolean') {
            return $this->rating == 1 ? 'Yes' : 'No';
        }
        return $this->rating;
    }

    public function evaluation()
    {
        return $this->belongsTo(TeacherEvaluation::class, 'teacher_evaluation_id');
    }

    public function statement()
    {
        return $this->belongsTo(EvaluationStatement::class, 'evaluation_statement_id');
    }
}

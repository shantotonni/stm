<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationStatement extends Model
{
    use HasFactory;

    protected $table = 'evaluation_statements';
    public $primaryKey = 'id';

    protected $fillable = ['statement', 'order', 'is_active'];

    public function responses()
    {
        return $this->hasMany(TeacherEvaluationDetails::class);
    }
}

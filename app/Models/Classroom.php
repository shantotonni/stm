<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'capacity', 'type', 'equipment', 'is_available'
    ];

    protected $casts = [
        'is_available' => 'boolean'
    ];

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }
}

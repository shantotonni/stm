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
        'is_available' => 'boolean',
        'capacity' => 'integer',
    ];

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public static function getTypeLabels()
    {
        return [
            'lecture_hall' => 'Lecture Hall',
            'lab' => 'Laboratory',
            'seminar_room' => 'Seminar Room',
            'tutorial_room' => 'Tutorial Room'
        ];
    }

    public function getFormattedTypeAttribute()
    {
        $labels = self::getTypeLabels();
        return $labels[$this->type] ?? $this->type;
    }
}

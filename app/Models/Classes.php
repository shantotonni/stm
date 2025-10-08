<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id', 'class_date', 'actual_start_time', 'actual_end_time',
        'topic', 'description', 'status', 'created_by'
    ];


    public function schedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'class_id');
    }

    public function materials()
    {
        return $this->hasMany(ClassMaterial::class, 'class_id');
    }

    // Scope for filtering by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope for filtering by date
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('class_date', $date);
    }

}

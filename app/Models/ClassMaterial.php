<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'subject_id', 'teacher_id', 'title', 'description', 'file_name', 'file_path', 'file_type', 'file_size', 'download_count', 'is_public', 'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_public' => 'boolean'
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(StudentMaterialAccess::class, 'material_id');
    }
}

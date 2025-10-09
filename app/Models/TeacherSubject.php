<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSubject extends Model
{
    use HasFactory;

    protected $table = 'teacher_subjects';

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'session_id',
        'is_coordinator',
        'academic_year',
        'is_primary',
    ];

    // Relationships
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Sessions::class);
    }

    // Scopes for easy filtering
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeCoordinators($query)
    {
        return $query->where('is_coordinator', 1);
    }

    public function scopePrimaryTeachers($query)
    {
        return $query->where('is_primary', 1);
    }

    public static function assignSubject($teacherId, $subjectId, $data = [])
    {
        return self::updateOrCreate(
            [
                'teacher_id' => $teacherId,
                'subject_id' => $subjectId,
                'session_id' => $data['session_id'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
            ],
            [
                'is_coordinator' => $data['is_coordinator'] ?? 0,
                'is_primary' => $data['is_primary'] ?? 0,
            ]
        );
    }

    public static function bulkAssign($teacherId, array $subjects, $data = [])
    {
        $assignments = [];
        foreach ($subjects as $subjectId) {
            $assignments[] = self::assignSubject($teacherId, $subjectId, $data);
        }
        return $assignments;
    }

    public static function removeAssignment($teacherId, $subjectId, $sessionId = null, $academicYear = null)
    {
        $query = self::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId);

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        return $query->delete();
    }
}

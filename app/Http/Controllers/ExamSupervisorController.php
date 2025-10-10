<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamSupervisorController extends Controller
{
    public function index($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Get supervisors with pivot data
            $supervisors = $exam->supervisors()
                ->select('teachers.*')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $supervisors,
                'exam' => $exam,
                'statistics' => [
                    'total' => $supervisors->count(),
                    'chief' => $supervisors->where('pivot.role', 'chief')->count(),
                    'assistant' => $supervisors->where('pivot.role', 'assistant')->count(),
                    'invigilator' => $supervisors->where('pivot.role', 'invigilator')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supervisors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $examId)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'role' => ['required', Rule::in(['chief', 'assistant', 'invigilator'])]
        ]);

        try {
            $exam = Exam::findOrFail($examId);
            $teacher = Teacher::findOrFail($request->teacher_id);

            // Check if teacher is already assigned to this exam
            if ($exam->supervisors()->where('teacher_id', $request->teacher_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This teacher is already assigned as supervisor for this exam'
                ], 400);
            }

            // Check if exam date conflicts with other assignments
            $hasConflict = $this->checkScheduleConflict($request->teacher_id, $exam);

            if ($hasConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher has a scheduling conflict with another exam'
                ], 409);
            }

            // Attach supervisor to exam
            $exam->supervisors()->attach($request->teacher_id, [
                'role' => $request->role,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$teacher->name} added as {$request->role} successfully",
                'data' => $exam->supervisors()->find($request->teacher_id)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add supervisor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $examId, $supervisorId)
    {
        $request->validate([
            'role' => ['required', Rule::in(['chief', 'assistant', 'invigilator'])]
        ]);

        try {
            $exam = Exam::findOrFail($examId);

            // Check if supervisor is assigned to this exam
            if (!$exam->supervisors()->where('teacher_id', $supervisorId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supervisor not found for this exam'
                ], 404);
            }

            // Update role in pivot table
            $exam->supervisors()->updateExistingPivot($supervisorId, [
                'role' => $request->role,
                'updated_at' => now()
            ]);

            $teacher = Teacher::find($supervisorId);

            return response()->json([
                'success' => true,
                'message' => "{$teacher->name}'s role updated to {$request->role}",
                'data' => $exam->supervisors()->find($supervisorId)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update supervisor role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($examId, $supervisorId)
    {
        try {
            $exam = Exam::findOrFail($examId);
            $teacher = Teacher::findOrFail($supervisorId);

            // Check if supervisor exists
            if (!$exam->supervisors()->where('teacher_id', $supervisorId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supervisor not assigned to this exam'
                ], 404);
            }

            // Detach supervisor
            $exam->supervisors()->detach($supervisorId);

            return response()->json([
                'success' => true,
                'message' => "{$teacher->name} removed as supervisor successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove supervisor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function availableTeachers($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Get teachers not assigned to this exam
            $assignedTeacherIds = $exam->supervisors()->pluck('teacher_id');

            $availableTeachers = Teacher::whereNotIn('id', $assignedTeacherIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $availableTeachers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available teachers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request, $examId)
    {
        $request->validate([
            'supervisors' => 'required|array',
            'supervisors.*.teacher_id' => 'required|exists:teachers,id',
            'supervisors.*.role' => ['required', Rule::in(['chief', 'assistant', 'invigilator'])]
        ]);

        try {
            $exam = Exam::findOrFail($examId);
            $successCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($request->supervisors as $supervisor) {
                // Check if already assigned
                if ($exam->supervisors()->where('teacher_id', $supervisor['teacher_id'])->exists()) {
                    $teacher = Teacher::find($supervisor['teacher_id']);
                    $errors[] = "{$teacher->name} is already assigned";
                    continue;
                }

                // Attach supervisor
                $exam->supervisors()->attach($supervisor['teacher_id'], [
                    'role' => $supervisor['role'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $successCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} supervisors added successfully",
                'success_count' => $successCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function checkScheduleConflict($teacherId, $exam)
    {
        // Get all exams assigned to this teacher
        $teacherExams = Teacher::find($teacherId)->exams()
            ->where('exam_date', $exam->exam_date)
            ->get();

        foreach ($teacherExams as $existingExam) {
            // Check time overlap
            if ($this->timesOverlap(
                $exam->start_time,
                $exam->end_time,
                $existingExam->start_time,
                $existingExam->end_time
            )) {
                return true;
            }
        }

        return false;
    }

    private function timesOverlap($start1, $end1, $start2, $end2)
    {
        return ($start1 < $end2) && ($end1 > $start2);
    }
}

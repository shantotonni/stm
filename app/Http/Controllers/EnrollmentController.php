<?php

namespace App\Http\Controllers;

use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentEnrollment::query()
            ->join('students', 'student_enrollments.student_id', '=', 'students.id')
            ->join('programs', 'student_enrollments.program_id', '=', 'programs.id')
            ->join('sessions', 'student_enrollments.session_id', '=', 'sessions.id')
            ->join('subjects', 'student_enrollments.subject_id', '=', 'subjects.id')
            ->select(
                'student_enrollments.*',
                'students.name as student_name',
                'programs.name as program_name',
                'sessions.name as session_name',
                'subjects.name as subject_name',
                'subjects.year'
            );

        // Apply filters
        if ($request->filled('program_id')) {
            $query->where('student_enrollments.program_id', $request->program_id);
        }

        if ($request->filled('session_id')) {
            $query->where('student_enrollments.session_id', $request->session_id);
        }

        if ($request->filled('is_active')) {
            $query->where('student_enrollments.is_active', $request->is_active);
        }

        if ($request->filled('student_id')) {
            $query->where('student_enrollments.student_id', $request->student_id);
        }

        $enrollments = $query->orderBy('student_enrollments.created_at', 'desc')->get();

        return response()->json($enrollments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'program_id' => 'required|exists:programs,id',
            'session_id' => 'required|exists:sessions,id',
            'subject_id' => 'required|exists:subjects,id',
            'enrollment_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        // Check if enrollment already exists
        $exists = StudentEnrollment::where([
            'student_id' => $validated['student_id'],
            'subject_id' => $validated['subject_id'],
            'session_id' => $validated['session_id'],
            'program_id' => $validated['program_id'],
        ])->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Student is already enrolled in this subject for the selected session and program.'
            ], 422);
        }

        $enrollment = StudentEnrollment::create([
            'student_id' => $validated['student_id'],
            'program_id' => $validated['program_id'],
            'session_id' => $validated['session_id'],
            'subject_id' => $validated['subject_id'],
            'enrollment_date' => $validated['enrollment_date'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Student enrolled successfully!',
            'data' => $enrollment
        ], 201);
    }

    public function show($id)
    {
        $enrollment = StudentEnrollment::with(['student', 'program', 'session', 'subject'])
            ->findOrFail($id);

        return response()->json($enrollment);
    }

    public function update(Request $request, $id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'session_id' => 'required|exists:sessions,id',
            'subject_ids' => 'required|array|exists:subjects,id',
            'enrollment_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        // Check if updated enrollment conflicts with existing one
        $exists = StudentEnrollment::where([
            'student_id' => $enrollment->student_id,
            'subject_id' => $validated['subject_ids'][0],
            'session_id' => $validated['session_id'],
            'program_id' => $validated['program_id'],
        ])->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'An enrollment with these details already exists.'
            ], 422);
        }

        $enrollment->update([
            'program_id' => $validated['program_id'],
            'session_id' => $validated['session_id'],
            'subject_id' => $validated['subject_ids'][0],
            'enrollment_date' => $validated['enrollment_date'],
            'is_active' => $validated['is_active'] ?? $enrollment->is_active,
        ]);

        return response()->json([
            'message' => 'Enrollment updated successfully!',
            'data' => $enrollment
        ]);
    }

    public function destroy($id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);
        $enrollment->delete();

        return response()->json([
            'message' => 'Enrollment deleted successfully!'
        ]);
    }

    public function bulkEnroll(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'program_id' => 'required|exists:programs,id',
            'session_id' => 'required|exists:sessions,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'enrollment_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $enrolled = [];
        $skipped = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validated['subject_ids'] as $subjectId) {
                // Check if already enrolled
                $exists = StudentEnrollment::where([
                    'student_id' => $validated['student_id'],
                    'subject_id' => $subjectId,
                    'session_id' => $validated['session_id'],
                    'program_id' => $validated['program_id'],
                ])->exists();

                if (!$exists) {
                    try {
                        StudentEnrollment::create([
                            'student_id' => $validated['student_id'],
                            'program_id' => $validated['program_id'],
                            'session_id' => $validated['session_id'],
                            'subject_id' => $subjectId,
                            'enrollment_date' => $validated['enrollment_date'],
                            'is_active' => $validated['is_active'] ?? true,
                        ]);
                        $enrolled[] = $subjectId;
                    } catch (\Exception $e) {
                        $errors[] = "Subject ID {$subjectId}: " . $e->getMessage();
                    }
                } else {
                    $skipped[] = $subjectId;
                }
            }

            DB::commit();

            $message = count($enrolled) > 0
                ? sprintf('Successfully enrolled in %d subject(s)!', count($enrolled))
                : 'No new enrollments created.';

            if (count($skipped) > 0) {
                $message .= sprintf(' %d subject(s) already enrolled.', count($skipped));
            }

            return response()->json([
                'message' => $message,
                'enrolled_count' => count($enrolled),
                'skipped_count' => count($skipped),
                'errors' => $errors,
            ], count($enrolled) > 0 ? 201 : 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred during bulk enrollment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statistics()
    {
        $stats = [
            'total_enrollments' => StudentEnrollment::count(),
            'active_enrollments' => StudentEnrollment::where('is_active', 1)->count(),
            'inactive_enrollments' => StudentEnrollment::where('is_active', 0)->count(),
            'enrollments_by_program' => StudentEnrollment::join('programs', 'student_enrollments.program_id', '=', 'programs.id')
                ->select('programs.name', DB::raw('count(*) as count'))
                ->groupBy('programs.name')
                ->get(),
            'enrollments_by_session' => StudentEnrollment::join('sessions', 'student_enrollments.session_id', '=', 'sessions.id')
                ->select('sessions.name', DB::raw('count(*) as count'))
                ->groupBy('sessions.name')
                ->get(),
        ];

        return response()->json($stats);
    }
}

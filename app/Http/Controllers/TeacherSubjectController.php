<?php

namespace App\Http\Controllers;

use App\Models\Sessions;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TeacherSubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherSubject::with(['teacher', 'subject', 'session']);

        if ($request->filled('teacher_id')) {
            $query->byTeacher($request->teacher_id);
        }

        if ($request->filled('subject_id')) {
            $query->bySubject($request->subject_id);
        }

        if ($request->filled('session_id')) {
            $query->bySession($request->session_id);
        }

        if ($request->filled('academic_year')) {
            $query->byAcademicYear($request->academic_year);
        }

        if ($request->filled('is_coordinator')) {
            $query->coordinators();
        }

        $assignments = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($assignments);
    }

    public function create()
    {
        $teachers = Teacher::select('id', 'name', 'email')->get();
        $subjects = Subject::select('id', 'name', 'code')->get();
        $sessions = Sessions::select('id', 'name')->get();

        return response()->json([
            'teachers' => $teachers,
            'subjects' => $subjects,
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'session_id' => 'required|exists:sessions,id',
            'academic_year' => 'required|string|max:100',
            'is_coordinator' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if already exists
        $exists = TeacherSubject::where('teacher_id', $request->teacher_id)
            ->where('subject_id', $request->subject_id)
            ->where('session_id', $request->session_id)
            ->where('academic_year', $request->academic_year)
            ->first();

        if ($exists) {
            return response()->json([
                'message' => 'This assignment already exists',
            ], 409);
        }

        $assignment = TeacherSubject::create($request->all());
        $assignment->load(['teacher', 'subject', 'session']);

        return response()->json([
            'message' => 'Subject assigned successfully',
            'data' => $assignment,
        ], 201);
    }

    // Bulk assign subjects to a teacher
    public function bulkAssign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
            'session_id' => 'required|exists:sessions,id',
            'academic_year' => 'required|string|max:100',
            'is_coordinator' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $assignments = [];
            foreach ($request->subject_ids as $subjectId) {
                $assignment = TeacherSubject::updateOrCreate(
                    [
                        'teacher_id' => $request->teacher_id,
                        'subject_id' => $subjectId,
                        'session_id' => $request->session_id,
                        'academic_year' => $request->academic_year,
                    ],
                    [
                        'is_coordinator' => $request->is_coordinator ?? 0,
                        'is_primary' => $request->is_primary ?? 0,
                    ]
                );
                $assignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'message' => 'Subjects assigned successfully',
                'data' => $assignments,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to assign subjects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get specific assignment
    public function show($id)
    {
        $assignment = TeacherSubject::with(['teacher', 'subject', 'session'])->findOrFail($id);
        return response()->json($assignment);
    }

    // Update assignment
    public function update(Request $request, $id)
    {
        $assignment = TeacherSubject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'teacher_id' => 'exists:teachers,id',
            'subject_id' => 'exists:subjects,id',
            'session_id' => 'exists:sessions,id',
            'academic_year' => 'string|max:100',
            'is_coordinator' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assignment->update($request->all());
        $assignment->load(['teacher', 'subject', 'session']);

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => $assignment,
        ]);
    }

    // Delete assignment
    public function destroy($id)
    {
        $assignment = TeacherSubject::findOrFail($id);
        $assignment->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully',
        ]);
    }

    // Get subjects assigned to a specific teacher
    public function teacherSubjects($teacherId)
    {
        $teacher = Teacher::with(['teacherSubjects.subject', 'teacherSubjects.session'])
            ->findOrFail($teacherId);

        return response()->json($teacher->teacherSubjects);
    }

    // Get teachers assigned to a specific subject
    public function subjectTeachers($subjectId)
    {
        $assignments = TeacherSubject::with(['teacher', 'session'])
            ->where('subject_id', $subjectId)
            ->get();

        return response()->json($assignments);
    }

    // Sync subjects for a teacher (remove old, add new)
    public function syncSubjects(Request $request, $teacherId)
    {
        $validator = Validator::make($request->all(), [
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
            'session_id' => 'required|exists:sessions,id',
            'academic_year' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Remove old assignments for this session and year
            TeacherSubject::where('teacher_id', $teacherId)
                ->where('session_id', $request->session_id)
                ->where('academic_year', $request->academic_year)
                ->delete();

            // Add new assignments
            $assignments = [];
            foreach ($request->subject_ids as $subjectId) {
                $assignment = TeacherSubject::create([
                    'teacher_id' => $teacherId,
                    'subject_id' => $subjectId,
                    'session_id' => $request->session_id,
                    'academic_year' => $request->academic_year,
                    'is_coordinator' => 0,
                    'is_primary' => 0,
                ]);
                $assignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'message' => 'Subjects synced successfully',
                'data' => $assignments,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to sync subjects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

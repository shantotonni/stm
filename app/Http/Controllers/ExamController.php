<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $query = Exam::with(['subject', 'examType', 'teacher', 'department', 'classroom', 'session']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('subject', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('subject', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('exam_type_id')) {
            $query->where('exam_type_id', $request->exam_type_id);
        }

        if ($request->filled('start_date')) {
            $query->where('exam_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('exam_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(20);

        return response()->json($exams);
    }

    // Get upcoming exams
    public function upcoming()
    {
        $exams = Exam::with(['subject.department', 'examType', 'teacher'])
            ->upcoming()
            ->take(10)
            ->get();
        return response()->json($exams);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'exam_type_id' => 'required|exists:exam_types,id',
            'teacher_id' => 'required|exists:teachers,id',
            'session_id' => 'required|exists:sessions,id',
            'department_id' => 'required|exists:departments,id',
            'classroom_id' => 'required',
            'title' => 'required|string|max:200',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration_minutes' => 'required|integer|min:1',
            'total_marks' => 'required|integer|min:1',
            'pass_marks' => 'required|integer|min:1',
            'semester' => 'required|min:1',
            'year' => 'required|min:1|max:5',
            'instructions' => 'nullable|string',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'supervisor_ids' => 'nullable|array',
            'supervisor_ids.*' => 'exists:teachers,id',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled'
        ]);


        DB::beginTransaction();
        try {
            //$exam = Exam::create($validated);
            $exam = Exam::create(array_merge($validated, [
                'created_by' => Auth::user()->id,
                'status' => $request->status ?? 'scheduled'
            ]));

            // Assign students if provided
            if ($request->filled('student_ids')) {
                foreach ($request->student_ids as $studentId) {
                    $exam->students()->attach($studentId, [
                        'is_eligible' => true
                    ]);
                }
            } else {
                $students = Student::where('year', $validated['year'])
                    ->where('semester', $validated['semester'])
                    ->get();
                foreach ($students as $student) {
                    $exam->students()->attach($student->id, [
                        'is_eligible' => true
                    ]);
                }
            }

            if ($request->filled('supervisor_ids')) {
                foreach ($request->supervisor_ids as $index => $supervisorId) {
                    $exam->supervisors()->attach($supervisorId, [
                        'role' => $index === 0 ? 'chief' : 'invigilator'
                    ]);
                }
            }

            $exam->notifications()->create([
                'notification_type' => 'schedule',
                'message' => "Exam scheduled: {$exam->title} on " .
                    Carbon::parse($exam->exam_date)->format('d M Y') .
                    " at " . Carbon::parse($exam->start_time)->format('h:i A')
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Exam created successfully',
                'exam' => $exam->load(['subject', 'examType', 'teacher', 'department', 'classroom', 'session'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating exam',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $exam = Exam::with([
            'subject.department',
            'examType',
            'teacher',
            'students',
            'supervisors',
            'results.student',
            'notifications'
        ])->findOrFail($id);

        return response()->json($exam);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);

        $validated = $request->validate([
            'subject_id' => 'sometimes|exists:subjects,id',
            'exam_type_id' => 'sometimes|exists:exam_types,id',
            'teacher_id' => 'sometimes|exists:teachers,id',
            'title' => 'sometimes|string|max:200',
            'exam_date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'sometimes',
            'duration_minutes' => 'sometimes|integer|min:1',
            'total_marks' => 'sometimes|integer|min:1',
            'passing_marks' => 'sometimes|integer|min:1',
            'room_number' => 'nullable|string|max:50',
            'venue' => 'nullable|string',
            'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
            'instructions' => 'nullable|string',
            'syllabus_topics' => 'nullable|string'
        ]);

        $exam->update($validated);

        if ($request->has('exam_date') || $request->has('start_time')) {
            $exam->notifications()->create([
                'notification_type' => 'schedule',
                'message' => "Exam scheduled: {$exam->title} on " .
                    Carbon::parse($exam->exam_date)->format('d M Y') .
                    " at " . Carbon::parse($exam->start_time)->format('h:i A')
            ]);
        }

        return response()->json([
            'message' => 'Exam updated successfully',
            'exam' => $exam->load(['subject', 'examType', 'teacher'])
        ]);
    }

    public function cancel($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update(['status' => 'cancelled']);

        $exam->notifications()->create([
            'notification_type' => 'cancellation',
            'message' => "Exam cancelled: {$exam->title} scheduled on {$exam->exam_date->format('d M Y')}"
        ]);

        return response()->json([
            'message' => 'Exam cancelled successfully'
        ]);
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();

        return response()->json([
            'message' => 'Exam deleted successfully'
        ]);
    }

    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'semester' => 'required|integer',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $query = Exam::with(['subject.department', 'examType', 'teacher'])
            ->where('year', $validated['year'])
            ->where('semester', $validated['semester'])
            ->where('status', 'scheduled');

        if (isset($validated['department_id'])) {
            $query->whereHas('subject', function($q) use ($validated) {
                $q->where('department_id', $validated['department_id']);
            });
        }

        $exams = $query->orderBy('exam_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('exam_date');

        return response()->json($exams);
    }

    public function studentSchedule($studentId)
    {
        $student = Student::findOrFail($studentId);

        $exams = $student->exams()
            ->with(['subject.department', 'examType', 'teacher'])
            ->where('exam_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        return response()->json($exams);
    }

    public function teacherSchedule($teacherId)
    {
        $teacher = Teacher::findOrFail($teacherId);

        $exams = $teacher->exams()
            ->with(['subject.department', 'examType'])
            ->where('exam_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        $supervisedExams = $teacher->supervisedExams()
            ->with(['subject.department', 'examType'])
            ->where('exam_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'created_exams' => $exams,
            'supervised_exams' => $supervisedExams
        ]);
    }

    public function getDropdownData()
    {
        return response()->json([
            'subjects' => \App\Models\Subject::select('id', 'name')->get(),
            'exam_types' => \App\Models\ExamType::select('id', 'name')->get(),
            'teachers' => \App\Models\Teacher::select('id', 'name')->get(),
            'sessions' => \App\Models\Sessions::select('id', 'name')->get(),
            'departments' => \App\Models\Department::select('id', 'name')->get(),
            'classrooms' => \App\Models\Classroom::select('id', 'name')->get(),
        ]);
    }
}

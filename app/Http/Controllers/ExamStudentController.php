<?php

namespace App\Http\Controllers;

use App\Models\ExamStudent;
use App\Models\Student;
use Illuminate\Http\Request;

class ExamStudentController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamStudent::query();

        if ($request->exam_id) {
            $query->byExam($request->exam_id);
        }

        if ($request->attendance_status) {
            $query->where('attendance_status', $request->attendance_status);
        }

        if ($request->is_eligible !== null) {
            $query->where('is_eligible', $request->is_eligible);
        }

        return response()->json([
            'data' => $query->paginate($request->per_page ?? 50)
        ]);
    }

    // Bulk assign students to exam
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'is_eligible' => 'boolean',
        ]);

        $assigned = [];
        foreach ($request->student_ids as $studentId) {
            $examStudent = ExamStudent::updateOrCreate(
                [
                    'exam_id' => $request->exam_id,
                    'student_id' => $studentId,
                ],
                [
                    'is_eligible' => $request->is_eligible ?? true,
                    'attendance_status' => 'absent',
                ]
            );
            $assigned[] = $examStudent->load('student');
        }

        return response()->json([
            'message' => count($assigned) . ' students assigned successfully',
            'data' => $assigned
        ]);
    }

    // Auto assign all eligible students from a class/batch
    public function autoAssign(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:classes,id',
            'batch_id' => 'nullable|exists:batches,id',
        ]);

        $studentsQuery = Student::query();

        if ($request->class_id) {
            $studentsQuery->where('class_id', $request->class_id);
        }

        if ($request->batch_id) {
            $studentsQuery->where('batch_id', $request->batch_id);
        }

        $students = $studentsQuery->get();

        $assigned = 0;
        foreach ($students as $student) {
            ExamStudent::updateOrCreate(
                [
                    'exam_id' => $request->exam_id,
                    'student_id' => $student->id,
                ],
                [
                    'is_eligible' => true,
                    'attendance_status' => 'absent',
                ]
            );
            $assigned++;
        }

        return response()->json([
            'message' => "{$assigned} students auto-assigned successfully",
            'count' => $assigned
        ]);
    }

    // Update attendance
    public function updateAttendance(Request $request, $id)
    {
        $request->validate([
            'attendance_status' => 'required|in:present,absent,excused',
        ]);

        $examStudent = ExamStudent::findOrFail($id);
        $examStudent->update(['attendance_status' => $request->attendance_status]);

        return response()->json([
            'message' => 'Attendance updated successfully',
            'data' => $examStudent
        ]);
    }

    // Bulk update attendance
    public function bulkUpdateAttendance(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:exam_students,id',
            'attendance_status' => 'required|in:present,absent,excused',
        ]);

        ExamStudent::whereIn('id', $request->ids)
            ->update(['attendance_status' => $request->attendance_status]);

        return response()->json([
            'message' => count($request->ids) . ' students attendance updated'
        ]);
    }

    // Auto assign seats
    public function autoAssignSeats(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'start_number' => 'nullable|integer|min:1',
        ]);

        $count = ExamStudent::autoAssignSeats(
            $request->exam_id,
            $request->start_number ?? 1
        );

        return response()->json([
            'message' => "{$count} students assigned seats automatically",
            'count' => $count
        ]);
    }

    // Update single seat
    public function updateSeat(Request $request, $id)
    {
        $request->validate([
            'seat_number' => 'required|string|max:20',
        ]);

        $examStudent = ExamStudent::findOrFail($id);
        $examStudent->assignSeat($request->seat_number);

        return response()->json([
            'message' => 'Seat number updated successfully',
            'data' => $examStudent
        ]);
    }

    // Remove student from exam
    public function destroy($id)
    {
        $examStudent = ExamStudent::findOrFail($id);
        $examStudent->delete();

        return response()->json([
            'message' => 'Student removed from exam'
        ]);
    }

    // Bulk remove
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:exam_students,id',
        ]);

        ExamStudent::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => count($request->ids) . ' students removed'
        ]);
    }

    // Get available students for exam (not yet assigned)
    public function availableStudents(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $assignedIds = ExamStudent::where('exam_id', $request->exam_id)
            ->pluck('student_id');

        $query = Student::whereNotIn('id', $assignedIds);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }

    // Get exam statistics
    public function statistics($examId)
    {
        $total = ExamStudent::where('exam_id', $examId)->count();
        $eligible = ExamStudent::where('exam_id', $examId)->eligible()->count();
        $present = ExamStudent::where('exam_id', $examId)->present()->count();
        $absent = ExamStudent::where('exam_id', $examId)->absent()->count();
        $excused = ExamStudent::where('exam_id', $examId)
            ->where('attendance_status', 'excused')->count();

        return response()->json([
            'total' => $total,
            'eligible' => $eligible,
            'present' => $present,
            'absent' => $absent,
            'excused' => $excused,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ]);
    }
}

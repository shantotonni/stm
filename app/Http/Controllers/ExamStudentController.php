<?php

namespace App\Http\Controllers;

use App\Models\ExamStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamStudentController extends Controller
{
    // Get all students for an exam
    public function index(Request $request, $examId)
    {
        $query = ExamStudent::with(['student' => function($q) {
            $q->select('id', 'name', 'roll_no', 'email');
        }])
            ->where('exam_id', $examId);

        // Filters
        if ($request->has('attendance_status')) {
            $query->where('attendance_status', $request->attendance_status);
        }

        if ($request->has('is_eligible')) {
            $query->where('is_eligible', $request->is_eligible);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%");
            });
        }

        $examStudents = $query->orderBy('seat_number')->paginate(50);

        return response()->json($examStudents);
    }

    // Assign students to exam (bulk)
    public function assignStudents(Request $request, $examId)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'auto_generate_seats' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $assigned = [];
            foreach ($request->student_ids as $studentId) {
                // Check if already assigned
                $exists = ExamStudent::where('exam_id', $examId)
                    ->where('student_id', $studentId)
                    ->exists();

                if (!$exists) {
                    $seatNumber = null;
                    if ($request->auto_generate_seats) {
                        $seatNumber = ExamStudent::generateSeatNumber($examId);
                    }

                    $examStudent = ExamStudent::create([
                        'exam_id' => $examId,
                        'student_id' => $studentId,
                        'is_eligible' => true,
                        'seat_number' => $seatNumber
                    ]);

                    $assigned[] = $examStudent->load('student');
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($assigned) . ' students assigned successfully',
                'data' => $assigned
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Update single exam student
    public function update(Request $request, $examId, $id)
    {
        $request->validate([
            'is_eligible' => 'boolean',
            'attendance_status' => 'in:present,absent,excused',
            'seat_number' => 'nullable|string|max:20'
        ]);

        $examStudent = ExamStudent::where('exam_id', $examId)
            ->where('id', $id)
            ->firstOrFail();

        $examStudent->update($request->only([
            'is_eligible',
            'attendance_status',
            'seat_number'
        ]));

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $examStudent->load('student')
        ]);
    }

    // Bulk update attendance
    public function bulkUpdateAttendance(Request $request, $examId)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'attendance_status' => 'required|in:present,absent,excused'
        ]);

        ExamStudent::where('exam_id', $examId)
            ->whereIn('id', $request->student_ids)
            ->update(['attendance_status' => $request->attendance_status]);

        return response()->json([
            'message' => 'Attendance updated for ' . count($request->student_ids) . ' students'
        ]);
    }

    // Auto-generate seat numbers
    public function autoGenerateSeats(Request $request, $examId)
    {
        $request->validate([
            'prefix' => 'nullable|string',
            'start_from' => 'nullable|integer|min:1'
        ]);

        $prefix = $request->prefix ?? 'SEAT-';
        $startFrom = $request->start_from ?? 1;

        $examStudents = ExamStudent::where('exam_id', $examId)
            ->whereNull('seat_number')
            ->orWhere('seat_number', '')
            ->orderBy('student_id')
            ->get();

        $counter = $startFrom;
        foreach ($examStudents as $examStudent) {
            $seatNumber = $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
            $examStudent->update(['seat_number' => $seatNumber]);
            $counter++;
        }

        return response()->json([
            'message' => $examStudents->count() . ' seat numbers generated',
            'generated' => $examStudents->count()
        ]);
    }

    // Remove student from exam
    public function destroy($examId, $id)
    {
        $examStudent = ExamStudent::where('exam_id', $examId)
            ->where('id', $id)
            ->firstOrFail();

        $examStudent->delete();

        return response()->json([
            'message' => 'Student removed from exam'
        ]);
    }

    // Get available students (not assigned to this exam)
    public function availableStudents($examId)
    {
        $assignedStudentIds = ExamStudent::where('exam_id', $examId)
            ->pluck('student_id');

        $students = Student::whereNotIn('id', $assignedStudentIds)
            ->select('id', 'name', 'roll_number', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($students);
    }

    // Get exam statistics
    public function statistics($examId)
    {
        $total = ExamStudent::where('exam_id', $examId)->count();
        $eligible = ExamStudent::where('exam_id', $examId)->where('is_eligible', true)->count();
        $present = ExamStudent::where('exam_id', $examId)->where('attendance_status', 'present')->count();
        $absent = ExamStudent::where('exam_id', $examId)->where('attendance_status', 'absent')->count();
        $excused = ExamStudent::where('exam_id', $examId)->where('attendance_status', 'excused')->count();

        return response()->json([
            'total' => $total,
            'eligible' => $eligible,
            'ineligible' => $total - $eligible,
            'present' => $present,
            'absent' => $absent,
            'excused' => $excused,
            'pending' => $total - ($present + $absent + $excused)
        ]);
    }
}

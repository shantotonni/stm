<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamStudentController extends Controller
{
    // Get all students for an exam
    public function index(Exam $exam)
    {
        $students = $exam->students()
            ->select('students.*')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number, // Roll/Registration number
                    'name' => $student->name,
                    'roll_no' => $student->roll_no,
                    'year' => $student->year,
                    'semester' => $student->semester,
                    'seat_number' => $student->pivot->seat_number,
                    'is_eligible' => (bool) $student->pivot->is_eligible,
                    'attendance_status' => $student->pivot->attendance_status,
                ];
            });

        return response()->json([
            'data' => $students
        ]);
    }

    /**
     * 3️⃣ Bulk add students by year and semester
     * POST /api/exams/{exam}/students/bulk/assign
     */
    public function bulkAssign(Request $request, Exam $exam)
    {
        $request->validate([
            'year' => 'required|integer',
            'semester' => 'required|integer',
        ]);

        // Get all students matching year & semester
        $students = Student::where('year', $request->year)
            ->where('semester', $request->semester)
            ->where('department_id', $exam->department_id) // Same department
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'message' => 'No students found for this criteria'
            ], 404);
        }

        $enrolledCount = 0;
        $skippedCount = 0;

        foreach ($students as $student) {
            // Check if already enrolled
            if (!$exam->students()->where('student_id', $student->id)->exists()) {
                $exam->students()->attach($student->id, [
                    'is_eligible' => true,
                ]);
                $enrolledCount++;
            } else {
                $skippedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully enrolled {$enrolledCount} students",
            'enrolled' => $enrolledCount,
            'skipped' => $skippedCount,
            'total_found' => $students->count()
        ]);
    }

    public function assignSeats(Exam $exam)
    {
        try {
            DB::beginTransaction();

            // Get all enrolled students (only eligible ones)
            $students = $exam->students()
                ->wherePivot('is_eligible', true)
                ->orderBy('students.roll_no', 'asc') // Sort by roll number
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible students enrolled in this exam'
                ], 404);
            }

            $seatNumber = 1;
            $assignedCount = 0;

            foreach ($students as $student) {
                // Generate seat number with padding
                $seatCode = 'SEAT-' . str_pad($seatNumber, 3, '0', STR_PAD_LEFT);

                // Update pivot table
                $exam->students()->updateExistingPivot($student->id, [
                    'seat_number' => $seatCode,
                    'updated_at' => now()
                ]);

                $seatNumber++;
                $assignedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned seats to {$assignedCount} student(s)",
                'data' => [
                    'total_assigned' => $assignedCount,
                    'seat_range' => [
                        'from' => 'SEAT-001',
                        'to' => 'SEAT-' . str_pad($assignedCount, 3, '0', STR_PAD_LEFT)
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Seat assignment failed', [
                'exam_id' => $exam->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign seats. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
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

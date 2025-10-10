<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttendance;
use App\Models\ExamStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamAttendanceController extends Controller
{
    public function getExamAttendance($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Exam এ যারা enrolled আছে তাদের সাথে attendance data join করা
            $students = DB::table('exam_students')
                ->join('students', 'exam_students.student_id', '=', 'students.id')
                ->leftJoin('exam_attendance', function($join) use ($examId) {
                    $join->on('students.id', '=', 'exam_attendance.student_id')
                        ->where('exam_attendance.exam_id', '=', $examId);
                })
                ->where('exam_students.exam_id', $examId)
                ->where('exam_students.is_eligible', 1) // শুধু enrolled students
                ->select(
                    'students.id',
                    'students.name',
                    'students.student_id_number',
                    'students.roll_no',
                    'students.email',
                    'exam_students.is_eligible',
                    'exam_students.attendance_status',
                    'exam_attendance.id as attendance_id',
                    'exam_attendance.check_in_time',
                    'exam_attendance.check_out_time',
                    'exam_attendance.verified_by'
                )
                ->orderBy('students.roll_no')
                ->get();

            $attendance = ExamAttendance::with(['student', 'verifier'])
                ->where('exam_id', $examId)
                ->whereNotNull('check_in_time')
                ->get();

            $totalStudents = $exam->students()->count();
            $presentCount = $attendance->count();
            $absentCount = $totalStudents - $presentCount;

            return response()->json([
                'success' => true,
                'exam' => $exam,
                'attendance' => $students,
                'statistics' => [
                    'total' => $totalStudents,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'percentage' => $totalStudents > 0 ? ($presentCount / $totalStudents) * 100 : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $attendance = ExamAttendance::where('exam_id', $request->exam_id)
            ->where('student_id', $validated['student_id'])
            ->first();

        if ($attendance){
            $attendance->check_in_time = now();
            $attendance->save();
        }else{
            $attendance = ExamAttendance::create([
                'exam_id' => $validated['exam_id'],
                'student_id' => $validated['student_id'],
                'check_in_time' => now(),
                'verified_by' => Auth::user()->id,
                'notes' => $validated['notes'] ?? null
            ]);
        }

        // Update exam_students table
        $exam = Exam::findOrFail($validated['exam_id']);
        $exam->students()->updateExistingPivot($validated['student_id'], [
            'attendance_status' => 'present'
        ]);

        return response()->json([
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance
        ], 201);
    }

    public function checkOut(Request $request, $id)
    {
        $attendance = ExamAttendance::findOrFail($id);
        $attendance->update(['check_out_time' => now()]);

        return response()->json([
            'message' => 'Check-out recorded successfully'
        ]);
    }

    public function markAbsent(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id'
        ]);

        try {
            $attendance = ExamAttendance::updateOrCreate(
                [
                    'exam_id' => $request->exam_id,
                    'student_id' => $request->student_id
                ],
                [
                    'verified_by' => Auth::user()->id,
                    'check_in_time' => null,
                    'check_out_time' => null
                ]
            );

            $exam = Exam::findOrFail($request->exam_id);
            $exam->students()->updateExistingPivot($request->student_id, [
                'attendance_status' => 'absent'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student marked as absent',
                'attendance' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark absent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk check-in for multiple students
     */
    public function bulkCheckIn(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        try {
            $successCount = 0;
            $errors = [];

            foreach ($request->student_ids as $studentId) {
                // Check eligibility
                $isEligible = ExamStudent::where('exam_id', $request->exam_id)
                    ->where('student_id', $studentId)
                    ->where('is_eligible', 1)
                    ->exists();

                if (!$isEligible) {
                    $errors[] = "Student ID {$studentId} is not enrolled";
                    continue;
                }

                // Check if already checked in
                $exists = ExamStudent::where('exam_id', $request->exam_id)
                    ->where('student_id', $studentId)
                    ->exists();

                if ($exists) {
                    $errors[] = "Student ID {$studentId} already checked in";
                    continue;
                }

                ExamAttendance::create([
                    'exam_id' => $request->exam_id,
                    'student_id' => $studentId,
                    'attendance_status' => 'present',
                    'check_in_time' => now(),
                    'verified_by' => Auth::user()->id
                ]);

                // Update exam_students table
                $exam = Exam::findOrFail($request->exam_id);
                $exam->students()->updateExistingPivot($studentId, [
                    'attendance_status' => 'present'
                ]);

                $successCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$successCount} students checked in successfully",
                'success_count' => $successCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk check-in failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function examAttendance($examId)
    {
        $exam = Exam::with('subject')->findOrFail($examId);

        $attendance = ExamAttendance::with(['student', 'verifier'])
            ->where('exam_id', $examId)
            ->get();

        $totalStudents = $exam->students()->count();
        $presentCount = $attendance->count();
        $absentCount = $totalStudents - $presentCount;

        return response()->json([
            'exam' => $exam,
            'attendance' => $attendance,
            'statistics' => [
                'total' => $totalStudents,
                'present' => $presentCount,
                'absent' => $absentCount,
                'percentage' => $totalStudents > 0 ? ($presentCount / $totalStudents) * 100 : 0
            ]
        ]);
    }
}

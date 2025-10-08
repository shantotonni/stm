<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttendance;
use Illuminate\Http\Request;

class ExamAttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'verified_by' => 'required|exists:teachers,id',
            'notes' => 'nullable|string'
        ]);

        $attendance = ExamAttendance::create([
            'exam_id' => $validated['exam_id'],
            'student_id' => $validated['student_id'],
            'check_in_time' => now(),
            'verified_by' => $validated['verified_by'],
            'notes' => $validated['notes'] ?? null
        ]);

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

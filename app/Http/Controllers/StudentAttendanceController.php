<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentAttendanceController extends Controller
{
    /**
     * Get student's own attendance summary
     */
    public function getMyAttendance(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $subjectId = $request->subject_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $attendanceData = $student->calculateAttendancePercentage($subjectId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $attendanceData,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching student attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student's monthly attendance details
     */
    public function getMonthlyAttendance(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $year = $request->year ?? now()->year;
            $month = $request->month ?? now()->month;

            $monthlyData = $student->getMonthlyAttendance($year, $month);

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'month' => $month,
                    'attendances' => $monthlyData['attendances'],
                    'summary' => $monthlyData['summary'],
                    'percentage' => $monthlyData['percentage'],
                    'is_eligible' => $monthlyData['percentage'] >= 75,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching monthly attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch monthly attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student's subject-wise attendance
     */
    public function getSubjectWiseAttendance(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            // Get all subjects the student is enrolled in
            $subjects = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('class_schedules.department_id', $student->department_id)
                ->select('subjects.id', 'subjects.name', 'subjects.code')
                ->distinct()
                ->get();

            $subjectAttendance = $subjects->map(function ($subject) use ($student) {
                $data = $student->calculateAttendancePercentage($subject->id);
                return [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                    'subject_code' => $subject->code,
                    'total_classes' => $data['total_classes'],
                    'attended_classes' => $data['attended_classes'],
                    'absent_classes' => $data['absent_classes'],
                    'percentage' => $data['percentage'],
                    'is_eligible' => $data['is_eligible'],
                    'status' => $data['status'],
                ];
            });

            // Overall summary
            $totalClasses = $subjectAttendance->sum('total_classes');
            $attendedClasses = $subjectAttendance->sum('attended_classes');
            $overallPercentage = $totalClasses > 0
                ? round(($attendedClasses / $totalClasses) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'subjects' => $subjectAttendance,
                    'overall' => [
                        'total_classes' => $totalClasses,
                        'attended_classes' => $attendedClasses,
                        'percentage' => $overallPercentage,
                        'is_eligible' => $overallPercentage >= 75,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching subject-wise attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subject-wise attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student's attendance history
     */
    public function getAttendanceHistory(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $perPage = $request->per_page ?? 20;
            $status = $request->status;

            $query = StudentAttendance::with(['class.schedule.subject'])
                ->where('student_id', $student->id)
                ->orderBy('marked_at', 'desc');

            if ($status) {
                $query->where('attendance_status', $status);
            }

            if ($request->start_date && $request->end_date) {
                $query->whereHas('class', function ($q) use ($request) {
                    $q->whereBetween('class_date', [$request->start_date, $request->end_date]);
                });
            }

            $attendances = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attendances,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching attendance history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance warnings (students below 75%)
     */
    public function getAttendanceWarnings(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            $overallData = $student->calculateAttendancePercentage();

            $warnings = [];

            if ($overallData['percentage'] < 75) {
                $classesNeeded = ceil((75 * $overallData['total_classes'] - 100 * $overallData['attended_classes']) / 25);

                $warnings[] = [
                    'type' => 'overall',
                    'message' => 'Your overall attendance is below 75%. You are not eligible for exams.',
                    'current_percentage' => $overallData['percentage'],
                    'required_percentage' => 75,
                    'classes_needed' => max(0, $classesNeeded),
                    'severity' => 'high',
                ];
            }

            // Check subject-wise attendance
            $subjects = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('class_schedules.department_id', $student->department_id)
                ->select('subjects.id', 'subjects.name')
                ->distinct()
                ->get();

            foreach ($subjects as $subject) {
                $subjectData = $student->calculateAttendancePercentage($subject->id);
                if ($subjectData['percentage'] < 75 && $subjectData['total_classes'] > 0) {
                    $classesNeeded = ceil((75 * $subjectData['total_classes'] - 100 * $subjectData['attended_classes']) / 25);

                    $warnings[] = [
                        'type' => 'subject',
                        'subject_id' => $subject->id,
                        'subject_name' => $subject->name,
                        'message' => "Your attendance in {$subject->name} is below 75%.",
                        'current_percentage' => $subjectData['percentage'],
                        'required_percentage' => 75,
                        'classes_needed' => max(0, $classesNeeded),
                        'severity' => $subjectData['percentage'] < 70 ? 'high' : 'medium',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_warnings' => count($warnings) > 0,
                    'warnings_count' => count($warnings),
                    'warnings' => $warnings,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching attendance warnings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch warnings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

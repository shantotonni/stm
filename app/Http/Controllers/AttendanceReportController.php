<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceReportController extends Controller
{
    /**
     * Get attendance report with filters
     */
    public function getReport(Request $request): JsonResponse
    {
        try {
            $query = StudentAttendance::with(['student', 'class.schedule.subject', 'class.schedule.department']);

            // Apply filters
            if ($request->department_id) {
                $query->whereHas('class.schedule.department', function ($q) use ($request) {
                    $q->where('id', $request->department_id);
                });
            }

            if ($request->subject_id) {
                $query->whereHas('class.schedule.subject', function ($q) use ($request) {
                    $q->where('id', $request->subject_id);
                });
            }

            if ($request->student_id) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->attendance_status) {
                $query->where('attendance_status', $request->attendance_status);
            }

            if ($request->start_date && $request->end_date) {
                $query->whereHas('class', function ($q) use ($request) {
                    $q->whereBetween('class_date', [$request->start_date, $request->end_date]);
                });
            }

            if ($request->marked_by) {
                $query->where('marked_by', $request->marked_by);
            }

            // Get paginated results
            $perPage = $request->per_page ?? 50;
            $attendances = $query->orderBy('marked_at', 'desc')->paginate($perPage);

            // Calculate summary statistics
            $totalRecords = $query->count();
            $statusSummary = [
                'present' => (clone $query)->where('attendance_status', 'present')->count(),
                'absent' => (clone $query)->where('attendance_status', 'absent')->count(),
                'late' => (clone $query)->where('attendance_status', 'late')->count(),
                'excused' => (clone $query)->where('attendance_status', 'excused')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'summary' => [
                    'total_records' => $totalRecords,
                    'status_breakdown' => $statusSummary,
                    'attendance_rate' => $totalRecords > 0
                        ? round((($statusSummary['present'] + $statusSummary['late']) / $totalRecords) * 100, 2)
                        : 0,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error generating attendance report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student-wise attendance summary
     */
    public function getStudentSummary(Request $request): JsonResponse
    {
        try {
            $departmentId = $request->department_id;
            $subjectId = $request->subject_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $studentsQuery = Student::with(['department'])
                ->where('status', 'active');

            if ($departmentId) {
                $studentsQuery->where('department_id', $departmentId);
            }

            $students = $studentsQuery->get();

            $studentSummaries = $students->map(function ($student) use ($subjectId, $startDate, $endDate) {
                $attendanceData = $student->calculateAttendancePercentage($subjectId, $startDate, $endDate);
                return $attendanceData;
            });

            // Separate eligible and not eligible students
            $eligible = $studentSummaries->filter(fn($s) => $s['is_eligible']);
            $notEligible = $studentSummaries->filter(fn($s) => !$s['is_eligible']);

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $studentSummaries->values(),
                    'summary' => [
                        'total_students' => $studentSummaries->count(),
                        'eligible_count' => $eligible->count(),
                        'not_eligible_count' => $notEligible->count(),
                        'average_attendance' => round($studentSummaries->avg('percentage'), 2),
                        'eligible_percentage' => $studentSummaries->count() > 0
                            ? round(($eligible->count() / $studentSummaries->count()) * 100, 2)
                            : 0,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error generating student summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate student summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subject-wise attendance report
     */
    public function getSubjectWiseReport(Request $request): JsonResponse
    {
        try {
            $departmentId = $request->department_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $query = DB::table('student_attendances')
                ->join('classes', 'student_attendances.class_id', '=', 'classes.id')
                ->join('class_schedules', 'classes.schedule_id', '=', 'class_schedules.id')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->select(
                    'subjects.id as subject_id',
                    'subjects.name as subject_name',
                    DB::raw('COUNT(DISTINCT student_attendances.id) as total_records'),
                    DB::raw('COUNT(DISTINCT classes.id) as total_classes'),
                    DB::raw('COUNT(DISTINCT student_attendances.student_id) as total_students'),
                    DB::raw("SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                    DB::raw("SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_count"),
                    DB::raw("SUM(CASE WHEN attendance_status = 'excused' THEN 1 ELSE 0 END) as excused_count")
                )
                ->where('classes.status', 'completed')
                ->groupBy('subjects.id', 'subjects.name');

            if ($departmentId) {
                $query->where('class_schedules.department_id', $departmentId);
            }

            if ($startDate && $endDate) {
                $query->whereBetween('classes.class_date', [$startDate, $endDate]);
            }

            $results = $query->get();

            $subjectReports = $results->map(function ($item) {
                $attendanceRate = $item->total_records > 0
                    ? round((($item->present_count + $item->late_count) / $item->total_records) * 100, 2)
                    : 0;

                return [
                    'subject_id' => $item->subject_id,
                    'subject_name' => $item->subject_name,
                    'total_classes' => $item->total_classes,
                    'total_students' => $item->total_students,
                    'total_records' => $item->total_records,
                    'present_count' => $item->present_count,
                    'absent_count' => $item->absent_count,
                    'late_count' => $item->late_count,
                    'excused_count' => $item->excused_count,
                    'attendance_rate' => $attendanceRate,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $subjectReports,
                'summary' => [
                    'total_subjects' => $subjectReports->count(),
                    'average_attendance_rate' => round($subjectReports->avg('attendance_rate'), 2),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error generating subject-wise report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate subject-wise report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get department-wise attendance statistics
     */
    public function getDepartmentWiseReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $query = DB::table('student_attendances')
                ->join('classes', 'student_attendances.class_id', '=', 'classes.id')
                ->join('class_schedules', 'classes.schedule_id', '=', 'class_schedules.id')
                ->join('departments', 'class_schedules.department_id', '=', 'departments.id')
                ->select(
                    'departments.id as department_id',
                    'departments.name as department_name',
                    DB::raw('COUNT(DISTINCT student_attendances.id) as total_records'),
                    DB::raw('COUNT(DISTINCT classes.id) as total_classes'),
                    DB::raw('COUNT(DISTINCT student_attendances.student_id) as total_students'),
                    DB::raw("SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                    DB::raw("SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_count"),
                    DB::raw("SUM(CASE WHEN attendance_status = 'excused' THEN 1 ELSE 0 END) as excused_count")
                )
                ->where('classes.status', 'completed')
                ->groupBy('departments.id', 'departments.name');

            if ($startDate && $endDate) {
                $query->whereBetween('classes.class_date', [$startDate, $endDate]);
            }

            $results = $query->get();

            $departmentReports = $results->map(function ($item) {
                $attendanceRate = $item->total_records > 0
                    ? round((($item->present_count + $item->late_count) / $item->total_records) * 100, 2)
                    : 0;

                return [
                    'department_id' => $item->department_id,
                    'department_name' => $item->department_name,
                    'total_classes' => $item->total_classes,
                    'total_students' => $item->total_students,
                    'total_records' => $item->total_records,
                    'present_count' => $item->present_count,
                    'absent_count' => $item->absent_count,
                    'late_count' => $item->late_count,
                    'excused_count' => $item->excused_count,
                    'attendance_rate' => $attendanceRate,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $departmentReports,
                'summary' => [
                    'total_departments' => $departmentReports->count(),
                    'average_attendance_rate' => round($departmentReports->avg('attendance_rate'), 2),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error generating department-wise report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate department-wise report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance analytics for dashboard
     */
    public function getDashboardAnalytics(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
            $endDate = $request->end_date ?? now()->format('Y-m-d');

            // Overall statistics
            $totalClasses = Classes::whereBetween('class_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->count();

            $totalAttendanceRecords = StudentAttendance::whereHas('class', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('class_date', [$startDate, $endDate])
                    ->where('status', 'completed');
            })->count();

            $presentCount = StudentAttendance::whereHas('class', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('class_date', [$startDate, $endDate])
                    ->where('status', 'completed');
            })->where('attendance_status', 'present')->count();

            $lateCount = StudentAttendance::whereHas('class', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('class_date', [$startDate, $endDate])
                    ->where('status', 'completed');
            })->where('attendance_status', 'late')->count();

            $overallAttendanceRate = $totalAttendanceRecords > 0
                ? round((($presentCount + $lateCount) / $totalAttendanceRecords) * 100, 2)
                : 0;

            // Daily attendance trend (last 7 days)
            $dailyTrend = DB::table('student_attendances')
                ->join('classes', 'student_attendances.class_id', '=', 'classes.id')
                ->select(
                    DB::raw('DATE(classes.class_date) as date'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN attendance_status IN ('present', 'late') THEN 1 ELSE 0 END) as attended"),
                    DB::raw("ROUND((SUM(CASE WHEN attendance_status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as rate")
                )
                ->whereBetween('classes.class_date', [now()->subDays(7), now()])
                ->where('classes.status', 'completed')
                ->groupBy(DB::raw('DATE(classes.class_date)'))
                ->orderBy('date')
                ->get();

            // Low attendance students (below 75%)
            $lowAttendanceStudents = Student::where('status', 'Y')->get()
                ->map(function ($student) {
                    return $student->calculateAttendancePercentage();
                })
                ->filter(fn($s) => $s['percentage'] < 75 && $s['total_classes'] > 0)
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_classes' => $totalClasses,
                        'total_records' => $totalAttendanceRecords,
                        'present_count' => $presentCount,
                        'late_count' => $lateCount,
                        'overall_attendance_rate' => $overallAttendanceRate,
                    ],
                    'daily_trend' => $dailyTrend,
                    'low_attendance_students' => [
                        'count' => $lowAttendanceStudents->count(),
                        'students' => $lowAttendanceStudents->take(10), // Top 10 for dashboard
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error generating dashboard analytics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

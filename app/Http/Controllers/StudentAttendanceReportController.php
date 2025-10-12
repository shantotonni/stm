<?php

namespace App\Http\Controllers;

use App\Models\StudentAttendance;
use Illuminate\Http\Request;

class StudentAttendanceReportController extends ReportController
{
    public function index(Request $request)
    {

        $query = StudentAttendance::with(['student.department', 'class.schedule.subject']);

        // Role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all attendance
        } elseif ($this->isDepartmentHead()) {
            // Department Head: See attendance of their department students
            $query->whereHas('student', function ($q) {
                $q->where('department_id', $this->getUserDepartmentId());
            });
        } elseif ($this->isTeacher()) {
            // Teacher: See attendance of their class students
            $query->where('teacher_id', $this->getUserId());
        } elseif ($this->isStudent()) {
            // Student: See only their own attendance
            $query->where('student_id', $this->getUserId());
        } else {
            return $this->jsonError('Unauthorized access', 403);
        }

        // Apply filters
        if ($request->filled('department_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('subject_id')) {
            $query->whereHas('class.schedule.subject', function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            });
            //$query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('month')) {
            $query->whereMonth('marked_at', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('marked_at', $request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%");
            });
        }

        // Group by student and calculate statistics
        $attendanceData = $query->get()->groupBy('student_id')->map(function ($records) {
            $student = $records->first()->student;
            $totalClasses = $records->count();
            $present = $records->where('attendance_status', 'present')->count();
            $absent = $records->where('attendance_status', 'absent')->count();
            $percentage = $totalClasses > 0 ? round(($present / $totalClasses) * 100, 2) : 0;

            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'roll_number' => $student->roll_no,
                'department' => $student->department->name,
                'month' => date('m'),
                'total_classes' => $totalClasses,
                'present' => $present,
                'absent' => $absent,
                'percentage' => $percentage,
                'status_color' => $this->getAttendanceColor($percentage),
            ];
        });

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $paginatedData = $attendanceData->slice($offset, $perPage)->values();
        $total = $attendanceData->count();

        return $this->jsonResponse([
            'data' => $paginatedData,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    private function getAttendanceColor($percentage)
    {
        if ($percentage >= 90) {
            return 'green';
        } elseif ($percentage >= 75) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    public function summary(Request $request)
    {
        // Get overall attendance summary
        $studentId = $this->isStudent() ? $this->getUserId() : $request->student_id;

        if (!$studentId) {
            return $this->jsonError('Student ID is required');
        }

        $attendance = StudentAttendance::where('student_id', $studentId)
            ->when($request->has('subject_id'), function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            })
            ->get();

        $totalClasses = $attendance->count();
        $present = $attendance->where('status', 'present')->count();
        $absent = $attendance->where('status', 'absent')->count();
        $percentage = $totalClasses > 0 ? round(($present / $totalClasses) * 100, 2) : 0;

        return $this->jsonResponse([
            'total_classes' => $totalClasses,
            'present' => $present,
            'absent' => $absent,
            'percentage' => $percentage,
            'status_color' => $this->getAttendanceColor($percentage),
        ]);
    }
}

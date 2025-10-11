<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;

class ClassScheduleReportController extends ReportController
{
    public function index(Request $request)
    {
        $query = ClassSchedule::with(['subject', 'teacher', 'classroom', 'department']);

        // Role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all schedules
        } elseif ($this->isDepartmentHead()) {
            // Department Head: See all schedules in their department
            $query->where('department_id', $this->getUserDepartmentId());
        } elseif ($this->isTeacher()) {
            // Teacher: See only their own class schedules
            $query->where('teacher_id', $this->getUserId());
        } elseif ($this->isStudent()) {
            // Student: See schedules of enrolled classes
            $query->whereHas('enrolledStudents', function ($q) {
                $q->where('student_id', $this->getUserId());
            });
        } else {
            return $this->jsonError('Unauthorized access', 403);
        }

        // Apply filters
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('day')) {
            $query->where('day', $request->day);
        }

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('class_title', 'like', "%{$search}%")
                    ->orWhereHas('subject', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('teacher', function ($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $query->orderBy('day')->orderBy('start_time');

        // Pagination
        $perPage = $request->get('per_page', 50);
        $schedules = $query->paginate($perPage);

        // Transform data
        $schedules->getCollection()->transform(function ($schedule) {
            return [
                'id' => $schedule->id,
                'class_title' => $schedule->class_title,
                'day' => $schedule->day,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'subject' => $schedule->subject->name,
                'subject_code' => $schedule->subject->code,
                'teacher' => $schedule->teacher->name,
                'classroom' => $schedule->classroom->room_number,
                'building' => $schedule->classroom->building,
                'department' => $schedule->department->name,
                'color_code' => $schedule->color_code ?? $this->generateColorCode($schedule->id),
            ];
        });

        return $this->jsonResponse($schedules);
    }

    public function weekly(Request $request)
    {
        // Get weekly schedule in calendar format
        $query = ClassSchedule::with(['subject', 'teacher', 'classroom', 'department']);

        // Apply same role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all
        } elseif ($this->isDepartmentHead()) {
            $query->where('department_id', $this->getUserDepartmentId());
        } elseif ($this->isTeacher()) {
            $query->where('teacher_id', $this->getUserId());
        } elseif ($this->isStudent()) {
            $query->whereHas('enrolledStudents', function ($q) {
                $q->where('student_id', $this->getUserId());
            });
        }

        $schedules = $query->orderBy('day')->orderBy('start_time')->get();

        // Group by day
        $weeklySchedule = [
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => [],
            'Friday' => [],
            'Saturday' => [],
            'Sunday' => [],
        ];

        foreach ($schedules as $schedule) {
            $weeklySchedule[$schedule->day][] = [
                'id' => $schedule->id,
                'class_title' => $schedule->class_title,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'subject' => $schedule->subject->name,
                'teacher' => $schedule->teacher->name,
                'classroom' => $schedule->classroom->room_number,
                'color_code' => $schedule->color_code ?? $this->generateColorCode($schedule->id),
            ];
        }

        return $this->jsonResponse($weeklySchedule);
    }

    private function generateColorCode($id)
    {
        $colors = ['#4A90E2', '#50C878', '#FF6B6B', '#FFA07A', '#9B59B6', '#3498DB', '#E74C3C'];
        return $colors[$id % count($colors)];
    }
}

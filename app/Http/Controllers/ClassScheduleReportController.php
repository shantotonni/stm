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
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('day')) {
            $query->where('day_of_week', $request->day);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('search')) {
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
        $query->orderBy('day_of_week')->orderBy('start_time');

        // Pagination
        $perPage = $request->get('per_page', 50);
        $schedules = $query->paginate($perPage);

        // Transform data
        $schedules->getCollection()->transform(function ($schedule) {
            return [
                'id' => $schedule->id,
                'class_title' => $schedule->class_type,
                'day' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'subject' => $schedule->subject->name,
                'subject_code' => $schedule->subject->code,
                'teacher' => $schedule->teacher->name,
                'classroom' => $schedule->classroom->code,
                'building' => $schedule->classroom->name,
                'department' => $schedule->department->name,
                'color_code' => $schedule->color_code ?? $this->generateColorCode($schedule->id),
            ];
        });

        return $this->jsonResponse($schedules);
    }

    public function weekly(Request $request)
    {
        $query = ClassSchedule::with(['subject', 'teacher', 'classroom', 'department'])
            ->where('is_active', 1);

        // 🔒 Role-based Filtering
        if ($this->isDepartmentHead()) {
            $query->where('department_id', $this->getUserDepartmentId());
        } elseif ($this->isTeacher()) {
            $query->where('teacher_id', $this->getUserId());
        } elseif ($this->isStudent()) {
            $query->whereHas('enrolledStudents', function ($q) {
                $q->where('student_id', $this->getUserId());
            });
        }

        // 🎯 Get all schedules sorted by day + time
        $schedules = $query->orderByRaw("
        FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')
    ")->orderBy('start_time')->get();

        // 🗓️ Group by day
        $weeklySchedule = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        foreach ($schedules as $schedule) {
            $weeklySchedule[strtolower($schedule->day_of_week)][] = [
                'id' => $schedule->id,
                'subject' => $schedule->subject->name ?? 'N/A',
                'teacher' => $schedule->teacher->name ?? 'N/A',
                'department' => $schedule->department->name ?? 'N/A',
                'classroom' => $schedule->classroom->name ?? 'N/A',
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'class_type' => ucfirst($schedule->class_type ?? 'Lecture'),
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

<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExamScheduleReportController extends ReportController
{
    public function index(Request $request)
    {
        $query = Exam::with(['subject', 'teacher', 'classroom', 'department']);

        // Role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all exam schedules
        } elseif ($this->isDepartmentHead()) {
            // Department Head: See all exams in their department
            $query->where('department_id', $this->getUserDepartmentId());
        } elseif ($this->isTeacher()) {
            // Teacher: See exam schedules of their subjects
            $query->where('teacher_id', $this->getUserId());
        } elseif ($this->isStudent()) {
            // Student: See exam schedules of enrolled subjects
            $query->whereHas('subject.enrolledStudents', function ($q) {
                $q->where('student_id', $this->getUserId());
            });
        } else {
            return $this->jsonError('Unauthorized access', 403);
        }

        // Apply filters
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('exam_type')) {
            $query->where('exam_type', $request->exam_type);
        }

        if ($request->has('status')) {
            $status = $request->status;
            if ($status === 'upcoming') {
                $query->where('exam_date', '>=', Carbon::now());
            } elseif ($status === 'completed') {
                $query->where('exam_date', '<', Carbon::now());
            }
        }

        if ($request->has('from_date')) {
            $query->where('exam_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('exam_date', '<=', $request->to_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('exam_title', 'like', "%{$search}%")
                    ->orWhereHas('subject', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'exam_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $schedules = $query->paginate($perPage);

        // Transform data
        $schedules->getCollection()->transform(function ($schedule) {
            $examDate = Carbon::parse($schedule->exam_date);
            $now = Carbon::now();

            $status = $examDate->isFuture() ? 'upcoming' : 'completed';
            $daysUntil = $status === 'upcoming' ? $now->diffInDays($examDate) : null;

            return [
                'id' => $schedule->id,
                'exam_title' => $schedule->exam_title,
                'subject' => $schedule->subject->name,
                'subject_code' => $schedule->subject->code,
                'exam_type' => $schedule->exam_type,
                'exam_date' => $schedule->exam_date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'duration' => $schedule->duration,
                'department' => $schedule->department->name,
                'teacher' => $schedule->teacher->name,
                'classroom' => $schedule->classroom->room_number,
                'building' => $schedule->classroom->building,
                'total_marks' => $schedule->total_marks,
                'status' => $status,
                'days_until' => $daysUntil,
                'is_today' => $examDate->isToday(),
            ];
        });

        return $this->jsonResponse($schedules);
    }

    public function calendar(Request $request)
    {
        // Get exam schedules in calendar format (monthly view)
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $query = Exam::with(['subject', 'teacher', 'classroom', 'department'])
            ->whereMonth('exam_date', $month)
            ->whereYear('exam_date', $year);

        // Apply same role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all
        } elseif ($this->isDepartmentHead()) {
            $query->where('department_id', $this->getUserDepartmentId());
        } elseif ($this->isTeacher()) {
            $query->where('teacher_id', $this->getUserId());
        } elseif ($this->isStudent()) {
            $query->whereHas('subject.enrolledStudents', function ($q) {
                $q->where('student_id', $this->getUserId());
            });
        }

        $schedules = $query->orderBy('exam_date')->orderBy('start_time')->get();

        // Group by date
        $calendar = $schedules->groupBy(function ($schedule) {
            return Carbon::parse($schedule->exam_date)->format('Y-m-d');
        })->map(function ($daySchedules) {
            return $daySchedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'exam_title' => $schedule->exam_title,
                    'subject' => $schedule->subject->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'classroom' => $schedule->classroom->room_number,
                ];
            });
        });

        return $this->jsonResponse($calendar);
    }
}

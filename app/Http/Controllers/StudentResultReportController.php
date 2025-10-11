<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use Illuminate\Http\Request;

class StudentResultReportController extends ReportController
{
    public function index(Request $request)
    {
        $query = ExamResult::with(['student.department', 'exam.subject', 'exam']);

        // Role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all results
            // No additional filtering needed
        } elseif ($this->isDepartmentHead()) {
            // Department Head: See results of students whose teachers belong to their department
            $query->whereHas('student.teachers', function ($q) {
                $q->where('department_id', $this->getUserDepartmentId());
            });
        } elseif ($this->isTeacher()) {
            // Teacher: See results of their own students
            $query->whereHas('student.teachers', function ($q) {
                $q->where('teacher_id', $this->getUserId());
            });
        } elseif ($this->isStudent()) {
            // Student: See only their own results
            $query->where('student_id', $this->getUserId());
        } else {
            return $this->jsonError('Unauthorized access', 403);
        }

        // Apply filters
        if ($request->has('department_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('exam_type')) {
            $query->whereHas('exam', function ($q) use ($request) {
                $q->where('exam_type', $request->exam_type);
            });
        }

        if ($request->has('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'exam_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $results = $query->paginate($perPage);

        // Transform data
        $results->getCollection()->transform(function ($result) {
            return [
                'id' => $result->id,
                'student_name' => $result->student->name,
                'roll_number' => $result->student->roll_number,
                'registration_number' => $result->student->registration_number,
                'department' => $result->student->department->name,
                'session' => $result->student->session,
                'subject_name' => $result->subject->name,
                'exam_type' => $result->exam->exam_type,
                'theory_marks' => $result->theory_marks,
                'practical_marks' => $result->practical_marks,
                'viva_marks' => $result->viva_marks,
                'total_marks' => $result->total_marks,
                'grade' => $result->grade,
                'gpa' => $result->gpa,
                'pass_fail_status' => $result->pass_fail_status,
                'exam_date' => $result->exam_date,
                'semester' => $result->semester,
            ];
        });

        return $this->jsonResponse($results);
    }

    public function export(Request $request)
    {
        // Export logic (CSV/PDF) can be implemented here
        // For now, return a success message
        return $this->jsonResponse(['message' => 'Export functionality to be implemented']);
    }
}

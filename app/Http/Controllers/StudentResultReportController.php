<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Sessions;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentResultReportController extends ReportController
{
    public function index(Request $request)
    {
        $query = ExamResult::with(['student.department', 'exam.subject', 'exam','exam.examType'])->join('exams', 'exam_results.exam_id', '=', 'exams.id');

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
        if ($request->filled('department_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('exam_type')) {
            $query->whereHas('exam', function ($q) use ($request) {
                $q->where('exam_type_id', $request->exam_type);
            });
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'exams.exam_date');
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
                'roll_number' => $result->student->roll_no,
                'registration_number' => $result->student->student_id_number,
                'department' => $result->student->department->name,
                'session' => $result->student->session,
                'subject_name' => isset($result->exam->subject) ? $result->exam->subject->name :'',
                'exam_type' => $result->exam->examType->name,
                'theory_marks' => $result->theory_marks,
                'practical_marks' => $result->practical_marks,
                'viva_marks' => $result->viva_marks,
                'total_marks' => $result->total_marks,
                'grade' => $result->grade,
                'gpa' => $result->gpa,
                'pass_fail_status' => $result->status,
                'exam_date' => $result->exam_date,
                'semester' => $result->semester,
            ];
        });

        return $this->jsonResponse($results);
    }

    /**
     * Get semester exam results for a specific student
     */

    public function getSemesterResults(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'session_id' => 'required|exists:sessions,id'
        ]);

        $studentId = $request->student_id;
        $sessionId = $request->session_id;

        // Get student info
        $student = Student::with('department')->findOrFail($studentId);

        // Get session info
        $session = Sessions::findOrFail($sessionId);

        // Get all exams for this session with student results
        $exams = Exam::with([
            'subject',
            'examType',
            'teacher.user',
            'classroom',
            'studentResults' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }
        ])
            ->where('session_id', $sessionId)
            ->orderBy('subject_id')
            ->orderBy('exam_type_id')
            ->get();

        // Group results by subject, then by exam type
        $subjectResults = [];
        $semesterTotalMarksObtained = 0;
        $semesterTotalMaxMarks = 0;
        $semesterTotalGpa = 0;
        $subjectCount = 0;

        foreach ($exams as $exam) {
            $subjectId = $exam->subject_id;
            $examTypeId = $exam->exam_type_id;
            $subjectName = $exam->subject->name ?? 'Unknown Subject';
            $examTypeName = $exam->examType->name ?? 'N/A';

            // Initialize subject if not exists
            if (!isset($subjectResults[$subjectId])) {
                $subjectResults[$subjectId] = [
                    'subject_id' => $subjectId,
                    'subject_name' => $subjectName,
                    'subject_code' => $exam->subject->code ?? '',
                    'department' => $exam->subject->department->name ?? 'N/A',
                    'exam_types' => [],
                    'subject_total_marks_obtained' => 0,
                    'subject_total_max_marks' => 0,
                    'subject_percentage' => 0,
                    'subject_cgpa' => 0,
                    'subject_grade' => ''
                ];
            }

            // Initialize exam type if not exists
            if (!isset($subjectResults[$subjectId]['exam_types'][$examTypeId])) {
                $subjectResults[$subjectId]['exam_types'][$examTypeId] = [
                    'exam_type_id' => $examTypeId,
                    'exam_type_name' => $examTypeName,
                    'exams' => [],
                    'type_total_marks_obtained' => 0,
                    'type_total_max_marks' => 0,
                    'type_percentage' => 0,
                    'type_grade' => '',
                    'type_gpa' => 0
                ];
            }

            $result = $exam->studentResults->first();

            if ($result) {
                $marksObtained = $result->marks_obtained;
                $totalMarks = $exam->total_marks;
                $percentage = ($totalMarks > 0) ? ($marksObtained / $totalMarks) * 100 : 0;
                $gradeData = ExamResult::calculateGradeAndGpa($percentage);

                $examData = [
                    'exam_id' => $exam->id,
                    'marks_obtained' => $marksObtained,
                    'total_marks' => $totalMarks,
                    'percentage' => $percentage,
                    'grade' => $result->grade ?? $gradeData['grade'],
                    'gpa' => $result->gpa ?? $gradeData['gpa'],
                    'remarks' => $result->remarks ?? '',
                    'status' => $result->status,
                    'teacher' => $exam->teacher->user->name ?? 'N/A',
                    'classroom' => $exam->classroom->name ?? 'N/A',
                    'date' => $exam->date ? $exam->date->format('d M, Y') : 'N/A'
                ];

                // Add to exam type
                $subjectResults[$subjectId]['exam_types'][$examTypeId]['exams'][] = $examData;

                // Update exam type totals
                $subjectResults[$subjectId]['exam_types'][$examTypeId]['type_total_marks_obtained'] += $marksObtained;
                $subjectResults[$subjectId]['exam_types'][$examTypeId]['type_total_max_marks'] += $totalMarks;

                // Update subject totals
                $subjectResults[$subjectId]['subject_total_marks_obtained'] += $marksObtained;
                $subjectResults[$subjectId]['subject_total_max_marks'] += $totalMarks;

                // Update semester totals
                $semesterTotalMarksObtained += $marksObtained;
                $semesterTotalMaxMarks += $totalMarks;
            }
        }

        // Calculate percentages and grades for exam types and subjects
        foreach ($subjectResults as $subjectId => &$subjectData) {
            // Calculate exam type totals
            foreach ($subjectData['exam_types'] as $typeId => &$typeData) {
                if ($typeData['type_total_max_marks'] > 0) {
                    $typePercentage = ($typeData['type_total_marks_obtained'] / $typeData['type_total_max_marks']) * 100;
                    $typeData['type_percentage'] = round($typePercentage, 2);

                    $gradeData = ExamResult::calculateGradeAndGpa($typePercentage);
                    $typeData['type_grade'] = $gradeData['grade'];
                    $typeData['type_gpa'] = $gradeData['gpa'];
                }

                // Format numbers
                $typeData['type_total_marks_obtained'] = number_format($typeData['type_total_marks_obtained'], 2);
                $typeData['type_total_max_marks'] = number_format($typeData['type_total_max_marks'], 2);
            }

            // Convert exam_types array to indexed array
            $subjectData['exam_types'] = array_values($subjectData['exam_types']);

            // Calculate subject totals
            if ($subjectData['subject_total_max_marks'] > 0) {
                $subjectPercentage = ($subjectData['subject_total_marks_obtained'] / $subjectData['subject_total_max_marks']) * 100;
                $subjectData['subject_percentage'] = round($subjectPercentage, 2);

                $gradeData = ExamResult::calculateGradeAndGpa($subjectPercentage);
                $subjectData['subject_cgpa'] = $gradeData['gpa'];
                $subjectData['subject_grade'] = $gradeData['grade'];

                $semesterTotalGpa += $gradeData['gpa'];
                $subjectCount++;
            }

            // Format subject numbers
            $subjectData['subject_total_marks_obtained'] = number_format($subjectData['subject_total_marks_obtained'], 2);
            $subjectData['subject_total_max_marks'] = number_format($subjectData['subject_total_max_marks'], 2);
        }

        // Calculate overall semester summary
        $overallPercentage = $semesterTotalMaxMarks > 0
            ? ($semesterTotalMarksObtained / $semesterTotalMaxMarks) * 100
            : 0;

        $overallCGPA = $subjectCount > 0
            ? $semesterTotalGpa / $subjectCount
            : 0;

        $overallGradeData = ExamResult::calculateGradeAndGpa($overallPercentage);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->user->name ?? 'N/A',
                    'roll' => $student->roll_no ?? 'N/A',
                    'department' => $student->department->name ?? 'N/A'
                ],
                'session' => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'year' => $session->year ?? '',
                    'semester' => $session->semester ?? ''
                ],
                'subjects' => array_values($subjectResults),
                'semester_summary' => [
                    'total_subjects' => $subjectCount,
                    'total_marks_obtained' => number_format($semesterTotalMarksObtained, 2),
                    'total_max_marks' => number_format($semesterTotalMaxMarks, 2),
                    'percentage' => number_format($overallPercentage, 2),
                    'cgpa' => number_format($overallCGPA, 2),
                    'grade' => $overallGradeData['grade']
                ]
            ]
        ]);
    }


    public function getSemesterResultsBackup(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'session_id' => 'required|exists:sessions,id'
        ]);

        $studentId = $request->student_id;
        $sessionId = $request->session_id;

        // Get student info
        $student = Student::with('department')->findOrFail($studentId);

        // Get all exams for this session with student results
        $exams = Exam::with([
            'subject',
            'examType',
            'teacher.user',
            'classroom',
            'studentResults' => function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            }
        ])
            ->where('session_id', $sessionId)
            ->orderBy('subject_id')
            ->orderBy('exam_type_id')
            ->get();

        // Group results by subject
        $subjectResults = [];
        $semesterTotalMarksObtained = 0;
        $semesterTotalMaxMarks = 0;
        $semesterTotalGpa = 0;
        $subjectCount = 0;

        foreach ($exams as $exam) {
            $subjectId = $exam->subject_id;
            $subjectName = $exam->subject->name ?? 'Unknown Subject';

            if (!isset($subjectResults[$subjectId])) {
                $subjectResults[$subjectId] = [
                    'subject_id' => $subjectId,
                    'subject_name' => $subjectName,
                    'subject_code' => $exam->subject->code ?? '',
                    'exams' => [],
                    'total_marks_obtained' => 0,
                    'total_max_marks' => 0,
                    'percentage' => 0,
                    'cgpa' => 0
                ];
            }

            $result = $exam->studentResults->first();

            if ($result) {
                $marksObtained = $result->marks_obtained;
                $totalMarks = $exam->total_marks;
                $percentage = ($totalMarks > 0) ? ($marksObtained / $totalMarks) * 100 : 0;
                $gradeData = ExamResult::calculateGradeAndGpa($percentage);

                $examData = [
                    'exam_type' => $exam->examType->name ?? 'N/A',
                    'marks_obtained' => number_format($marksObtained, 2),
                    'total_marks' => number_format($totalMarks, 2),
                    'grade' => $result->grade ?? $gradeData['grade'],
                    'gpa' => $result->gpa ?? $gradeData['gpa'],
                    'remarks' => $result->remarks ?? '',
                    'status' => $result->status,
                    'teacher' => $exam->teacher->user->name ?? 'N/A',
                    'classroom' => $exam->classroom->name ?? 'N/A',
                    'date' => $exam->exam_date ? date('Y-m-d',strtotime($exam->exam_date)) : 'N/A'
                ];

                $subjectResults[$subjectId]['exams'][] = $examData;
                $subjectResults[$subjectId]['total_marks_obtained'] += $marksObtained;
                $subjectResults[$subjectId]['total_max_marks'] += $totalMarks;

                // Update semester totals
                $semesterTotalMarksObtained += $marksObtained;
                $semesterTotalMaxMarks += $totalMarks;
            }
        }

        // Calculate subject percentages and CGPA
        foreach ($subjectResults as $subjectId => &$subjectData) {
            if ($subjectData['total_max_marks'] > 0) {
                $subjectPercentage = ($subjectData['total_marks_obtained'] / $subjectData['total_max_marks']) * 100;
                $subjectData['percentage'] = number_format($subjectPercentage, 2);

                $gradeData = ExamResult::calculateGradeAndGpa($subjectPercentage);
                $subjectData['cgpa'] = $gradeData['gpa'];
                $subjectData['grade'] = $gradeData['grade'];

                $semesterTotalGpa += $gradeData['gpa'];
                $subjectCount++;
            }

            $subjectData['total_marks_obtained'] = number_format($subjectData['total_marks_obtained'], 2);
            $subjectData['total_max_marks'] = number_format($subjectData['total_max_marks'], 2);
        }

        // Calculate overall semester summary
        $overallPercentage = $semesterTotalMaxMarks > 0
            ? ($semesterTotalMarksObtained / $semesterTotalMaxMarks) * 100
            : 0;

        $overallCGPA = $subjectCount > 0
            ? $semesterTotalGpa / $subjectCount
            : 0;

        $overallGradeData = ExamResult::calculateGradeAndGpa($overallPercentage);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->user->name ?? 'N/A',
                    'roll' => $student->roll_no ?? 'N/A',
                    'department' => $student->department->name ?? 'N/A'
                ],
                'subjects' => array_values($subjectResults),
                'semester_summary' => [
                    'total_marks_obtained' => number_format($semesterTotalMarksObtained, 2),
                    'total_max_marks' => number_format($semesterTotalMaxMarks, 2),
                    'percentage' => number_format($overallPercentage, 2),
                    'cgpa' => number_format($overallCGPA, 2),
                    'grade' => $overallGradeData['grade']
                ]
            ]
        ]);
    }

    public function export(Request $request)
    {
        // Export logic (CSV/PDF) can be implemented here
        // For now, return a success message
        return $this->jsonResponse(['message' => 'Export functionality to be implemented']);
    }
}

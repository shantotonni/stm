<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
    public function getExamResults($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Get results with student information, ordered by marks (highest first)
            $results = ExamResult::with(['student:id,name,student_id,roll_number'])
                ->where('exam_id', $examId)
                ->orderBy('marks_obtained', 'desc')
                ->get();

            // Calculate statistics
            $statistics = [
                'total_students' => $results->count(),
                'passed' => $results->where('status', 'pass')->count(),
                'failed' => $results->where('status', 'fail')->count(),
                'average_marks' => $results->avg('marks_obtained') ?? 0,
                'highest_marks' => $results->max('marks_obtained') ?? 0,
                'lowest_marks' => $results->min('marks_obtained') ?? 0,
                'average_percentage' => $results->avg('percentage') ?? 0
            ];

            return response()->json([
                'success' => true,
                'results' => $results,
                'statistics' => $statistics,
                'exam' => $exam
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getExamStudents($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Get enrolled students who don't have results yet
            $students = $exam->enrolledStudents()
                ->select('students.id', 'students.name', 'students.student_id', 'students.roll_number')
                ->leftJoin('exam_results', function($join) use ($examId) {
                    $join->on('students.id', '=', 'exam_results.student_id')
                        ->where('exam_results.exam_id', '=', $examId);
                })
                ->whereNull('exam_results.id') // Only students without results
                ->orderBy('students.roll_number')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $students,
                'exam' => $exam
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'evaluated_by' => 'required|exists:users,id',
            'results' => 'required|array|min:1',
            'results.*.student_id' => 'required|exists:students,id',
            'results.*.marks_obtained' => 'required|numeric|min:0',
            'results.*.grade' => 'required|string|max:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exam = Exam::findOrFail($request->exam_id);
            $successCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($request->results as $resultData) {
                // Validate marks don't exceed total marks
                if ($resultData['marks_obtained'] > $exam->total_marks) {
                    $student = Student::find($resultData['student_id']);
                    $errors[] = "{$student->name}: Marks cannot exceed total marks ({$exam->total_marks})";
                    continue;
                }

                // Check if student is enrolled in this exam
                $isEnrolled = $exam->students()
                    ->where('student_id', $resultData['student_id'])
                    ->exists();

                if (!$isEnrolled) {
                    $student = Student::find($resultData['student_id']);
                    $errors[] = "{$student->name}: Not enrolled in this exam";
                    continue;
                }

                // Check if result already exists
                $existingResult = ExamResult::where('exam_id', $exam->id)
                    ->where('student_id', $resultData['student_id'])
                    ->first();

                if ($existingResult) {
                    $student = Student::find($resultData['student_id']);
                    $errors[] = "{$student->name}: Result already exists";
                    continue;
                }

                // Calculate percentage
                $percentage = ($resultData['marks_obtained'] / $exam->total_marks) * 100;

                // Determine pass/fail
                $status = $resultData['marks_obtained'] >= $exam->passing_marks ? 'pass' : 'fail';

                // Create result
                ExamResult::create([
                    'exam_id' => $exam->id,
                    'student_id' => $resultData['student_id'],
                    'marks_obtained' => $resultData['marks_obtained'],
                    'grade' => $resultData['grade'],
                    'status' => $status,
                    'percentage' => $percentage,
                    'evaluated_by' => $request->evaluated_by,
                    'published_at' => now() // Auto-publish
                ]);

                $successCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} results saved successfully",
                'success_count' => $successCount,
                'errors' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'marks_obtained' => 'required|numeric|min:0',
            'evaluated_by' => 'required|exists:users,id',
            'remarks' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exam = Exam::findOrFail($request->exam_id);

            // Validate marks
            if ($request->marks_obtained > $exam->total_marks) {
                return response()->json([
                    'success' => false,
                    'message' => "Marks cannot exceed total marks ({$exam->total_marks})"
                ], 400);
            }

            // Calculate grade and percentage
            $percentage = ($request->marks_obtained / $exam->total_marks) * 100;
            $grade = ExamResult::calculateGrade($percentage);
            $status = $request->marks_obtained >= $exam->passing_marks ? 'pass' : 'fail';

            // Create or update result
            $result = ExamResult::updateOrCreate(
                [
                    'exam_id' => $request->exam_id,
                    'student_id' => $request->student_id
                ],
                [
                    'marks_obtained' => $request->marks_obtained,
                    'grade' => $grade,
                    'status' => $status,
                    'percentage' => $percentage,
                    'remarks' => $request->remarks,
                    'evaluated_by' => $request->evaluated_by,
                    'published_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Result saved successfully',
                'data' => $result->load('student')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $resultId)
    {
        $validator = Validator::make($request->all(), [
            'marks_obtained' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = ExamResult::with('exam')->findOrFail($resultId);
            $exam = $result->exam;

            // Validate marks
            if ($request->marks_obtained > $exam->total_marks) {
                return response()->json([
                    'success' => false,
                    'message' => "Marks cannot exceed total marks ({$exam->total_marks})"
                ], 400);
            }

            // Recalculate everything
            $percentage = ($request->marks_obtained / $exam->total_marks) * 100;
            $grade = ExamResult::calculateGrade($percentage);
            $status = $request->marks_obtained >= $exam->passing_marks ? 'pass' : 'fail';

            // Update result
            $result->update([
                'marks_obtained' => $request->marks_obtained,
                'grade' => $grade,
                'status' => $status,
                'percentage' => $percentage,
                'remarks' => $request->remarks ?? $result->remarks
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Result updated successfully',
                'data' => $result->load('student')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($resultId)
    {
        try {
            $result = ExamResult::findOrFail($resultId);
            $studentName = $result->student->name;

            $result->delete();

            return response()->json([
                'success' => true,
                'message' => "Result for {$studentName} deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete result',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function gradeDistribution($examId)
    {
        try {
            $results = ExamResult::where('exam_id', $examId)->get();

            $distribution = [
                'A+' => 0, 'A' => 0, 'A-' => 0,
                'B+' => 0, 'B' => 0, 'B-' => 0,
                'C+' => 0, 'C' => 0, 'D' => 0, 'F' => 0
            ];

            foreach ($results as $result) {
                if (isset($distribution[$result->grade])) {
                    $distribution[$result->grade]++;
                }
            }

            return response()->json([
                'success' => true,
                'distribution' => $distribution
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get grade distribution'
            ], 500);
        }
    }

    public function studentResult($studentId, $examId)
    {
        try {
            $result = ExamResult::with(['exam', 'student'])
                ->where('student_id', $studentId)
                ->where('exam_id', $examId)
                ->first();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Result not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch result'
            ], 500);
        }
    }

    public function publishResults(Request $request, $examId)
    {
        try {
            $publish = $request->input('publish', true);

            ExamResult::where('exam_id', $examId)
                ->update([
                    'published_at' => $publish ? now() : null
                ]);

            $message = $publish ? 'Results published successfully' : 'Results unpublished successfully';

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update publish status'
            ], 500);
        }
    }
}

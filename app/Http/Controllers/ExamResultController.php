<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamResultController extends Controller
{
    // Submit single result
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'marks_obtained' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'evaluated_by' => 'required|exists:teachers,id'
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        // Validate marks don't exceed total
        if ($validated['marks_obtained'] > $exam->total_marks) {
            return response()->json([
                'message' => 'Marks obtained cannot exceed total marks'
            ], 422);
        }

        $result = new ExamResult($validated);
        $result->grade = $result->calculateGrade();
        $result->evaluated_at = now();
        $result->save();

        return response()->json([
            'message' => 'Result submitted successfully',
            'result' => $result->load(['exam', 'student', 'evaluator'])
        ], 201);
    }

    // Bulk submit results
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'evaluated_by' => 'required|exists:teachers,id',
            'results' => 'required|array',
            'results.*.student_id' => 'required|exists:students,id',
            'results.*.marks_obtained' => 'required|numeric|min:0'
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        DB::beginTransaction();
        try {
            $savedResults = [];

            foreach ($validated['results'] as $resultData) {
                if ($resultData['marks_obtained'] > $exam->total_marks) {
                    continue;
                }

                $result = ExamResult::updateOrCreate(
                    [
                        'exam_id' => $validated['exam_id'],
                        'student_id' => $resultData['student_id']
                    ],
                    [
                        'marks_obtained' => $resultData['marks_obtained'],
                        'grade' => (new ExamResult([
                            'marks_obtained' => $resultData['marks_obtained'],
                            'exam_id' => $validated['exam_id']
                        ]))->calculateGrade(),
                        'evaluated_by' => $validated['evaluated_by'],
                        'evaluated_at' => now()
                    ]
                );

                $savedResults[] = $result;
            }

            // Mark exam as completed
            $exam->update(['status' => 'completed']);

            // Create result notification
            $exam->notifications()->create([
                'notification_type' => 'result',
                'message' => "Results published for: {$exam->title}"
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Results submitted successfully',
                'count' => count($savedResults)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error submitting results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get results for an exam
    public function examResults($examId)
    {
        $exam = Exam::with(['subject', 'examType'])->findOrFail($examId);

        $results = ExamResult::with(['student', 'evaluator'])
            ->where('exam_id', $examId)
            ->orderBy('marks_obtained', 'desc')
            ->get();

        $stats = [
            'total_students' => $results->count(),
            'passed' => $results->where('marks_obtained', '>=', $exam->passing_marks)->count(),
            'failed' => $results->where('marks_obtained', '<', $exam->passing_marks)->count(),
            'average_marks' => $results->avg('marks_obtained'),
            'highest_marks' => $results->max('marks_obtained'),
            'lowest_marks' => $results->min('marks_obtained')
        ];

        return response()->json([
            'exam' => $exam,
            'results' => $results,
            'statistics' => $stats
        ]);
    }

    // Get student's all results
    public function studentResults($studentId)
    {
        $results = ExamResult::with(['exam.subject.department', 'exam.examType'])
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($results);
    }

    public function update(Request $request, $id)
    {
        $result = ExamResult::findOrFail($id);

        $validated = $request->validate([
            'marks_obtained' => 'sometimes|numeric|min:0',
            'remarks' => 'nullable|string'
        ]);

        if (isset($validated['marks_obtained'])) {
            if ($validated['marks_obtained'] > $result->exam->total_marks) {
                return response()->json([
                    'message' => 'Marks obtained cannot exceed total marks'
                ], 422);
            }
            $validated['grade'] = $result->calculateGrade();
        }

        $result->update($validated);

        return response()->json([
            'message' => 'Result updated successfully',
            'result' => $result
        ]);
    }
}

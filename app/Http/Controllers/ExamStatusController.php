<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExamStatusController extends Controller
{
    public function startExam($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            if ($exam->status !== 'scheduled') {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot start exam with status: {$exam->status}"
                ], 400);
            }

            // Optional: Check if exam date is today or past
            $examDate = Carbon::parse($exam->exam_date);
            $today = Carbon::today();

            if ($examDate->isFuture() && $examDate->diffInDays($today) > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start exam scheduled for future date'
                ], 400);
            }

            // Update status to ongoing
            $exam->update([
                'status' => 'ongoing',
                'actual_start_time' => now() // Optional: track actual start time
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exam started successfully',
                'exam' => $exam
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start exam',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function completeExam($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Validation: Can only complete ongoing exams
            if ($exam->status !== 'ongoing') {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot complete exam with status: {$exam->status}"
                ], 400);
            }

            // Update status to completed
            $exam->update([
                'status' => 'completed',
                'actual_end_time' => now() // Optional: track actual end time
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exam completed successfully',
                'exam' => $exam
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete exam',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelExam(Request $request, $examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            // Validation: Can only cancel scheduled or ongoing exams
            if (!in_array($exam->status, ['scheduled', 'ongoing'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot cancel exam with status: {$exam->status}"
                ], 400);
            }

            // Optional: Add cancellation reason
            $exam->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->input('reason'),
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exam cancelled successfully',
                'exam' => $exam
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel exam',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reopenExam($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            if ($exam->status !== 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only cancelled exams can be reopened'
                ], 400);
            }

            $exam->update([
                'status' => 'scheduled',
                'cancellation_reason' => null,
                'cancelled_at' => null,
                'cancelled_by' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exam reopened successfully',
                'exam' => $exam
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reopen exam'
            ], 500);
        }
    }

    public function autoUpdateStatuses()
    {
        try {
            $now = now();
            $updated = 0;

            // Start scheduled exams if exam date is today and time has passed
            $toStart = Exam::where('status', 'scheduled')
                ->where('exam_date', '<=', $now->toDateString())
                ->where(function($query) use ($now) {
                    $query->whereTime('start_time', '<=', $now->toTimeString())
                        ->orWhereNull('start_time');
                })
                ->get();

            foreach ($toStart as $exam) {
                $exam->update(['status' => 'ongoing']);
                $updated++;
            }

            // Complete ongoing exams if end time has passed
            $toComplete = Exam::where('status', 'ongoing')
                ->where('exam_date', '<', $now->toDateString())
                ->orWhere(function($query) use ($now) {
                    $query->where('status', 'ongoing')
                        ->where('exam_date', '=', $now->toDateString())
                        ->whereTime('end_time', '<', $now->toTimeString());
                })
                ->get();

            foreach ($toComplete as $exam) {
                $exam->update(['status' => 'completed']);
                $updated++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$updated} exam statuses updated",
                'updated_count' => $updated
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-update statuses'
            ], 500);
        }
    }

    public function availableTransitions($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);

            $transitions = [];

            switch ($exam->status) {
                case 'scheduled':
                    $transitions = ['ongoing', 'cancelled'];
                    break;
                case 'ongoing':
                    $transitions = ['completed', 'cancelled'];
                    break;
                case 'completed':
                    $transitions = []; // No transitions from completed
                    break;
                case 'cancelled':
                    $transitions = ['scheduled']; // Can reopen
                    break;
            }

            return response()->json([
                'success' => true,
                'current_status' => $exam->status,
                'available_transitions' => $transitions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transitions'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Get students enrolled in a specific class for attendance marking
     */
    public function getStudentsForClass(Request $request, $classId)
    {
        try {
            $class = Classes::with(['schedule.subject', 'schedule.department'])->findOrFail($classId);

            // Get students enrolled in this class's schedule
            $students = Student::with(['department'])
                ->whereHas('department', function ($query) use ($class) {
                    $query->where('id', $class->schedule->department_id);
                })
                ->where('status', 'Y')
                ->orderBy('roll_no')
                ->get();

            // Get existing attendance for this class
            $existingAttendance = StudentAttendance::where('class_id', $classId)->pluck('attendance_status', 'student_id');

            // Map students with their attendance status
            $studentsWithAttendance = $students->map(function ($student) use ($existingAttendance) {
                return [
                    'id' => $student->id,
                    'roll_number' => $student->roll_no,
                    'full_name' => $student->name,
                    'email' => $student->email,
                    'photo_url' => $student->photo_url,
                    'department' => $student->department->name ?? null,
                    'attendance_status' => $existingAttendance[$student->id] ?? null,
                    'is_marked' => isset($existingAttendance[$student->id]),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => [
                        'id' => $class->id,
                        'topic' => $class->topic,
                        'class_date' => $class->class_date->format('Y-m-d'),
                        'subject' => $class->schedule->subject->name ?? null,
                        'department' => $class->schedule->department->name ?? null,
                        'status' => $class->status,
                    ],
                    'students' => $studentsWithAttendance,
                    'total_students' => $studentsWithAttendance->count(),
                    'attendance_marked' => $existingAttendance->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching students for class: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch students for attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark or update bulk attendance for a class
     */
    public function markBulkAttendance(BulkAttendanceRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $classId = $request->class_id;
            $attendanceData = $request->attendance;
            $markedBy = auth()->id();

            $class = Classes::findOrFail($classId);

            $savedAttendance = [];
            $errors = [];

            foreach ($attendanceData as $record) {
                try {
                    $attendance = StudentAttendance::updateOrCreate(
                        [
                            'class_id' => $classId,
                            'student_id' => $record['student_id'],
                        ],
                        [
                            'attendance_status' => $record['attendance_status'],
                            'marked_at' => now(),
                            'marked_by' => $markedBy,
                            'remarks' => $record['remarks'] ?? null,
                        ]
                    );

                    $savedAttendance[] = $attendance;
                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $record['student_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Update class status to completed if not already
            if ($class->status === 'scheduled') {
                $class->update(['status' => 'completed']);
            }

            DB::commit();

            // Get attendance summary
            $summary = StudentAttendance::getClassSummary($classId);

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => [
                    'saved_count' => count($savedAttendance),
                    'error_count' => count($errors),
                    'summary' => $summary,
                    'errors' => $errors,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking bulk attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update single attendance record
     */
    public function updateAttendance(UpdateAttendanceRequest $request, $attendanceId): JsonResponse
    {
        try {
            $attendance = StudentAttendance::findOrFail($attendanceId);

            $attendance->update([
                'attendance_status' => $request->attendance_status,
                'remarks' => $request->remarks,
                'marked_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => new AttendanceResource($attendance),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance for a specific class
     */
    public function getClassAttendance($classId): JsonResponse
    {
        try {
            $class = Classes::with(['schedule.subject', 'schedule.department'])->findOrFail($classId);

            $attendances = StudentAttendance::with(['student.department', 'markedBy'])
                ->where('class_id', $classId)
                ->get();

            $summary = StudentAttendance::getClassSummary($classId);

            return response()->json([
                'success' => true,
                'data' => [
                    'class' => [
                        'id' => $class->id,
                        'topic' => $class->topic,
                        'class_date' => $class->class_date->format('Y-m-d'),
                        'subject' => $class->schedule->subject->name ?? null,
                        'department' => $class->schedule->department->name ?? null,
                    ],
                    'attendances' => AttendanceResource::collection($attendances),
                    'summary' => $summary,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching class attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch class attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance($attendanceId): JsonResponse
    {
        try {
            $attendance = StudentAttendance::findOrFail($attendanceId);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting attendance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all students as present for a class
     */
    public function markAllPresent(Request $request, $classId)
    {
        DB::beginTransaction();
        try {
            $class = Classes::findOrFail($classId);
            $markedBy = auth()->id();

            // Get all students in the department
            $students = Student::where('department_id', $class->schedule->department_id)
                ->where('status', 'Y')
                ->get();

            $savedCount = 0;

            foreach ($students as $student) {
                StudentAttendance::updateOrCreate(
                    [
                        'class_id' => $classId,
                        'student_id' => $student->id,
                    ],
                    [
                        'attendance_status' => 'present',
                        'marked_at' => now(),
                        'marked_by' => $markedBy,
                        'remarks' => 'Marked all present',
                    ]
                );
                $savedCount++;
            }

            // Update class status
            if ($class->status === 'scheduled') {
                $class->update(['status' => 'completed']);
            }

            DB::commit();

            $summary = StudentAttendance::getClassSummary($classId);

            return response()->json([
                'success' => true,
                'message' => 'All students marked as present',
                'data' => [
                    'saved_count' => $savedCount,
                    'summary' => $summary,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking all present: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all as present',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

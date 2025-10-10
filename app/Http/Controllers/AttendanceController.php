<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function getTodayClasses()
    {
        try {
            $today = Carbon::now()->format('l'); // Day name (Monday, Tuesday, etc.)

            $classes = ClassSchedule::with(['subject.department', 'subject'])
                ->where('day_of_week', 'saturday')
                ->where('is_active', 1)
                ->orderBy('start_time')
                ->get()
                ->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'class_id' => $schedule->id,
                        'class_name' =>  'N/A',
                        'department' =>  'N/A',
                        'subject' => $schedule->subject->name ?? 'N/A',
                        'time' => Carbon::parse($schedule->start_time)->format('h:i A') . ' - ' .
                            Carbon::parse($schedule->end_time)->format('h:i A'),
                        'room' => $schedule->room ?? 'N/A',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching classes: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getClassStudents($classId, Request $request)
    {
        try {
            $date = $request->input('date', Carbon::now()->format('Y-m-d'));

            // Get all students enrolled in this class
            $students = Student::where('class_id', $classId)
                ->where('is_active', 1)
                ->orderBy('roll_number')
                ->get()
                ->map(function($student) use ($classId, $date) {
                    // Check if attendance already marked for this date
                    $attendance = StudentAttendance::where('class_id', $classId)
                        ->where('student_id', $student->id)
                        ->whereDate('marked_at', $date)
                        ->first();

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'roll_number' => $student->roll_number,
                        'photo' => $student->photo ?? asset('images/default-avatar.png'),
                        'attendance_status' => $attendance->attendance_status ?? 'present',
                        'remarks' => $attendance->remarks ?? null,
                        'attendance_id' => $attendance->id ?? null,
                        'already_marked' => $attendance ? true : false
                    ];
                });

            $classInfo = Classes::with('department')->find($classId);

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'class_info' => [
                        'name' => $classInfo->name ?? 'N/A',
                        'department' => $classInfo->department->name ?? 'N/A',
                        'total_students' => $students->count()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching students: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveAttendance(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.remarks' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $classId = $request->class_id;
            $date = $request->date;
            $userId = auth()->id();

            foreach ($request->attendances as $attendance) {
                StudentAttendance::updateOrCreate(
                    [
                        'class_id' => $classId,
                        'student_id' => $attendance['student_id'],
                        'marked_at' => Carbon::parse($date)->startOfDay()
                    ],
                    [
                        'attendance_status' => $attendance['status'],
                        'remarks' => $attendance['remarks'] ?? null,
                        'marked_by' => $userId,
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error saving attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceReport(Request $request)
    {
        $classId = $request->input('class_id');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        try {
            $report = StudentAttendance::with(['student', 'class'])
                ->where('class_id', $classId)
                ->whereBetween('marked_at', [$startDate, $endDate])
                ->get()
                ->groupBy('student_id')
                ->map(function($attendances, $studentId) {
                    $student = $attendances->first()->student;
                    $total = $attendances->count();
                    $present = $attendances->where('attendance_status', 'present')->count();
                    $absent = $attendances->where('attendance_status', 'absent')->count();
                    $late = $attendances->where('attendance_status', 'late')->count();
                    $excused = $attendances->where('attendance_status', 'excused')->count();

                    return [
                        'student_id' => $studentId,
                        'student_name' => $student->name,
                        'roll_number' => $student->roll_number,
                        'total_days' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'excused' => $excused,
                        'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $report->values()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ], 500);
        }
    }
}

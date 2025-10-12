<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\ClassSchedule;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $query = Classes::with(['schedule', 'creator']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('topic', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status != '') {
            $query->byStatus($request->status);
        }

        if ($request->has('date') && $request->date != '') {
            $query->byDate($request->date);
        }

        $classes = $query->orderBy('class_date', 'desc')
            ->orderBy('actual_start_time', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|integer',
            'class_date' => 'required|date',
            'actual_start_time' => 'nullable|date_format:H:i',
            'actual_end_time' => 'nullable|date_format:H:i|after:actual_start_time',
            'topic' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'status' => 'nullable|in:scheduled,completed,cancelled,rescheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $class = Classes::create([
            'schedule_id' => $request->schedule_id,
            'class_date' => $request->class_date,
            'actual_start_time' => $request->actual_start_time,
            'actual_end_time' => $request->actual_end_time,
            'topic' => $request->topic,
            'description' => $request->description,
            'status' => $request->status ?? 'scheduled',
            'created_by' => auth()->id() ?? 1, // Replace with actual auth user
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => $class->load(['schedule', 'creator'])
        ], 201);
    }

    public function show($id)
    {
        $class = Classes::with(['schedule', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $class
        ]);
    }

    public function update(Request $request, $id)
    {
        $class = Classes::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'sometimes|required|integer',
            'class_date' => 'sometimes|required|date',
            'actual_start_time' => 'nullable|date_format:H:i:s',
            'actual_end_time' => 'nullable|date_format:H:i:s|after:actual_start_time',
            'topic' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'status' => 'nullable|in:scheduled,completed,cancelled,rescheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $class->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully',
            'data' => $class->load(['schedule', 'creator'])
        ]);
    }

    public function destroy($id)
    {
        $class = Classes::findOrFail($id);
        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully'
        ]);
    }

    public function getStatuses()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'scheduled',
                'completed',
                'cancelled',
                'rescheduled'
            ]
        ]);
    }

    public function getSchedules(){
        $schedules = ClassSchedule::query()->with('subject','teacher','classroom','session')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($schedules);
    }

    public function dailyClassGenerate(){
        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
        $schedules = ClassSchedule::where('day_of_week', $today)
            ->where('is_active', 1)
            ->get();
        $user = Auth::user();

        foreach ($schedules as $schedule) {
            Classes::firstOrCreate([
                'schedule_id' => $schedule->id,
                'class_date' => now()->toDateString(),
            ], [
                'status' => 'scheduled',
                'created_by' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully'
        ]);
    }

    public function getDepartments()
    {
        try {
            $departments = Department::select('id', 'name', 'code')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $departments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSubjectsByDepartment($departmentId)
    {
        try {
            // Get subjects linked to this department through class_schedules
            $subjects = Subject::select('subjects.id', 'subjects.name', 'subjects.code')
                ->join('class_schedules', 'subjects.id', '=', 'class_schedules.subject_id')
                ->where('class_schedules.department_id', $departmentId)
                ->distinct()
                ->orderBy('subjects.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subjects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load subjects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClasses(Request $request)
    {
        try {
            $query = Classes::with(['schedule.subject', 'schedule.department']);

            // Filter by department
            if ($request->department_id) {
                $query->whereHas('schedule', function ($q) use ($request) {
                    $q->where('department_id', $request->department_id);
                });
            }

            // Filter by subject
            if ($request->subject_id) {
                $query->whereHas('schedule', function ($q) use ($request) {
                    $q->where('subject_id', $request->subject_id);
                });
            }

            // Filter by date
            if ($request->date_filter) {
                switch ($request->date_filter) {
                    case 'today':
                        $query->whereDate('class_date', today());
                        break;
                    case 'week':
                        $query->whereBetween('class_date', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]);
                        break;
                    case 'month':
                        $query->whereMonth('class_date', now()->month)
                            ->whereYear('class_date', now()->year);
                        break;
                }
            }

            // Get classes
            $classes = $query->orderBy('class_date', 'desc')
                ->orderBy('actual_start_time', 'desc')
                ->get();

            // Format response with attendance info
            $formattedClasses = $classes->map(function ($class) {
                // Get attendance summary
                $attendanceSummary = DB::table('student_attendances')
                    ->select(
                        DB::raw('COUNT(*) as total'),
                        DB::raw("SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present_count"),
                        DB::raw("SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                        DB::raw("SUM(CASE WHEN attendance_status = 'late' THEN 1 ELSE 0 END) as late_count")
                    )
                    ->where('class_id', $class->id)
                    ->first();

                // Get total students in department
                $totalStudents = DB::table('students')
                    ->where('department_id', $class->schedule->department_id)
                    ->where('status', 'Y')
                    ->count();

                return [
                    'id' => $class->id,
                    'topic' => $class->topic,
                    'description' => $class->description,
                    'class_date' => $class->class_date->format('Y-m-d'),
                    'actual_start_time' => $class->actual_start_time,
                    'actual_end_time' => $class->actual_end_time,
                    'status' => $class->status,
                    'subject_name' => $class->schedule->subject->name ?? 'N/A',
                    'subject_code' => $class->schedule->subject->code ?? 'N/A',
                    'department_name' => $class->schedule->department->name ?? 'N/A',
                    'total_students' => $totalStudents,
                    'attendance_marked' => $attendanceSummary->total > 0,
                    'present_count' => $attendanceSummary->present_count ?? 0,
                    'absent_count' => $attendanceSummary->absent_count ?? 0,
                    'late_count' => $attendanceSummary->late_count ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedClasses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single class details
     */
    public function getClassDetails($classId): JsonResponse
    {
        try {
            $class = Classes::with([
                'schedule.subject',
                'schedule.department',
                'creator'
            ])->findOrFail($classId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $class->id,
                    'topic' => $class->topic,
                    'description' => $class->description,
                    'class_date' => $class->class_date->format('Y-m-d'),
                    'actual_start_time' => $class->actual_start_time,
                    'actual_end_time' => $class->actual_end_time,
                    'status' => $class->status,
                    'subject' => [
                        'id' => $class->schedule->subject->id ?? null,
                        'name' => $class->schedule->subject->name ?? 'N/A',
                        'code' => $class->schedule->subject->code ?? 'N/A',
                    ],
                    'department' => [
                        'id' => $class->schedule->department->id ?? null,
                        'name' => $class->schedule->department->name ?? 'N/A',
                    ],
                    'created_by' => [
                        'id' => $class->creator->id ?? null,
                        'name' => $class->creator->name ?? 'N/A',
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

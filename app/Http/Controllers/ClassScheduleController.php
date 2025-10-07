<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Sessions;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassSchedule::with(['subject', 'teacher', 'classroom', 'session']);

        if ($request->has('day_of_week') && $request->day_of_week !== '') {
            $query->where('day_of_week', $request->day_of_week);
        }
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->has('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        if ($request->has('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->has('class_type') && $request->class_type !== '') {
            $query->where('class_type', $request->class_type);
        }
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('subject', function($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                    ->orWhereHas('teacher', function($tq) use ($search) {
                        $tq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('classroom', function($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate($request->per_page ?? 10);

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'session_id' => 'required|exists:sessions,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'class_type' => 'required|in:lecture,practical,tutorial,seminar',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for conflicts
        $conflicts = $this->checkConflicts($request->all());
        if (!empty($conflicts)) {
            return response()->json([
                'errors' => ['conflict' => $conflicts]
            ], 422);
        }

        $schedule = ClassSchedule::create($request->all());

        return response()->json([
            'message' => 'Class schedule created successfully',
            'schedule' => $schedule->load(['subject', 'teacher', 'classroom', 'session'])
        ], 201);
    }

    public function show($id)
    {
        $schedule = ClassSchedule::with(['subject', 'teacher', 'classroom', 'session'])->findOrFail($id);
        return response()->json($schedule);
    }

    public function update(Request $request, $id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'session_id' => 'required|exists:sessions,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'class_type' => 'required|in:lecture,practical,tutorial,seminar',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for conflicts (excluding current schedule)
        $conflicts = $this->checkConflicts($request->all(), $id);
        if (!empty($conflicts)) {
            return response()->json([
                'errors' => ['conflict' => $conflicts]
            ], 422);
        }

        $schedule->update($request->all());

        return response()->json([
            'message' => 'Class schedule updated successfully',
            'schedule' => $schedule->load(['subject', 'teacher', 'classroom', 'session'])
        ]);
    }

    public function destroy($id)
    {
        $schedule = ClassSchedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'message' => 'Class schedule deleted successfully'
        ]);
    }

    private function checkConflicts($data, $excludeId = null)
    {
        $conflicts = [];

        // Base query for time overlap check
        $baseQuery = function() use ($data, $excludeId) {
            $query = ClassSchedule::where('day_of_week', $data['day_of_week'])
                ->where('session_id', $data['session_id'])
                ->where('is_active', 1);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            // Check time overlap
            $query->where(function($q) use ($data) {
                // Case 1: New start time falls within existing schedule
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    // Case 2: New end time falls within existing schedule
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                    // Case 3: New schedule completely covers existing schedule
                    ->orWhere(function($q2) use ($data) {
                        $q2->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                    })
                    // Case 4: Existing schedule completely covers new schedule
                    ->orWhere(function($q3) use ($data) {
                        $q3->where('start_time', '>=', $data['start_time'])
                            ->where('end_time', '<=', $data['end_time']);
                    });
            });

            return $query;
        };

        // 1. Check teacher conflict (একই teacher একই সময়ে দুই জায়গায় থাকতে পারবে না)
        $teacherConflict = $baseQuery()
            ->where('teacher_id', $data['teacher_id'])
            ->with(['subject', 'classroom'])
            ->first();

        if ($teacherConflict) {
            $timeRange = date('g:i A', strtotime($teacherConflict->start_time)) . ' - ' .
                date('g:i A', strtotime($teacherConflict->end_time));
            $conflicts[] = "Teacher is already scheduled for {$teacherConflict->subject->name} in {$teacherConflict->classroom->code} at {$timeRange}";
        }

        // 2. Check classroom conflict (একই classroom একই সময়ে একাধিক class হতে পারবে না)
        $classroomConflict = $baseQuery()
            ->where('classroom_id', $data['classroom_id'])
            ->with(['subject', 'teacher'])
            ->first();

        if ($classroomConflict) {
            $timeRange = date('g:i A', strtotime($classroomConflict->start_time)) . ' - ' .
                date('g:i A', strtotime($classroomConflict->end_time));
            $conflicts[] = "Classroom is already booked for {$classroomConflict->subject->name} by {$classroomConflict->teacher->name} at {$timeRange}";
        }

        // 3. Check if same subject is already scheduled at same time (duplicate prevention)
        $subjectConflict = $baseQuery()
            ->where('subject_id', $data['subject_id'])
            ->with(['teacher', 'classroom'])
            ->first();

        if ($subjectConflict) {
            $timeRange = date('g:i A', strtotime($subjectConflict->start_time)) . ' - ' .
                date('g:i A', strtotime($subjectConflict->end_time));
            $conflicts[] = "This subject is already scheduled by {$subjectConflict->teacher->name} in {$subjectConflict->classroom->code} at {$timeRange}";
        }

        // 4. Check minimum break time between classes (optional - 10 minutes break)
        $breakTime = 10; // minutes
        $newStartTime = strtotime($data['start_time']);
        $newEndTime = strtotime($data['end_time']);

        // Check if teacher has back-to-back classes without break
        $teacherPreviousClass = ClassSchedule::where('day_of_week', $data['day_of_week'])
            ->where('session_id', $data['session_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->where('is_active', 1)
            ->where(function($q) use ($data, $newStartTime, $breakTime) {
                // Class ending just before new class
                $q->whereRaw('TIME_TO_SEC(end_time) > ?', [$newStartTime - ($breakTime * 60)])
                    ->whereRaw('TIME_TO_SEC(end_time) <= ?', [$newStartTime]);
            });

        if ($excludeId) {
            $teacherPreviousClass->where('id', '!=', $excludeId);
        }

        $previousClass = $teacherPreviousClass->first();

        if ($previousClass) {
            $endTime = date('g:i A', strtotime($previousClass->end_time));
            $startTime = date('g:i A', strtotime($data['start_time']));
            $conflicts[] = "Teacher needs at least {$breakTime} minutes break between classes (Previous class ends at {$endTime}, new class starts at {$startTime})";
        }

        // 5. Check classroom availability time constraint (optional)
        // যদি classroom এর available time limit থাকে
        if (isset($data['classroom_id'])) {
            $classroom = \App\Models\Classroom::find($data['classroom_id']);
            if ($classroom && !$classroom->is_available) {
                $conflicts[] = "Classroom {$classroom->code} is currently not available";
            }
        }

        // 6. Validate time logic (end time must be after start time)
        if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
            $conflicts[] = "End time must be after start time";
        }

        // 7. Check maximum class duration (optional - max 3 hours)
        $duration = (strtotime($data['end_time']) - strtotime($data['start_time'])) / 3600; // hours
        if ($duration > 3) {
            $conflicts[] = "Class duration cannot exceed 3 hours (Current duration: " . round($duration, 1) . " hours)";
        }

        // 8. Check minimum class duration (optional - min 30 minutes)
        if ($duration < 0.5) {
            $conflicts[] = "Class duration must be at least 30 minutes (Current duration: " . round($duration * 60) . " minutes)";
        }

        return $conflicts;
    }

    // Get dropdown data for form
    public function getFormData()
    {
        return response()->json([
            'subjects' => Subject::where('is_active', 1)->get(['id', 'name', 'code']),
            'teachers' => Teacher::query()->get(['id', 'name', 'BMDC_NO']),
            'classrooms' => Classroom::where('is_available', 1)->get(['id', 'name', 'code']),
            'sessions' => Sessions::where('is_active', 1)->get(['id', 'name']),
        ]);
    }

    // Get weekly schedule view
    public function getWeeklySchedule(Request $request)
    {
        $sessionId = $request->session_id;
        $schedules = ClassSchedule::with(['subject', 'teacher', 'classroom'])
            ->where('session_id', $sessionId)
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return response()->json($schedules);
    }

    // Get statistics
    public function getStats()
    {
        $stats = [
            'total_schedules' => ClassSchedule::count(),
            'active_schedules' => ClassSchedule::where('is_active', 1)->count(),
            'by_day' => ClassSchedule::selectRaw('day_of_week, count(*) as count')
                ->where('is_active', 1)
                ->groupBy('day_of_week')
                ->get(),
            'by_type' => ClassSchedule::selectRaw('class_type, count(*) as count')
                ->where('is_active', 1)
                ->groupBy('class_type')
                ->get()
        ];

        return response()->json($stats);
    }
}

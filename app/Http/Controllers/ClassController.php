<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}

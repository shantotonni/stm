<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $query = Classroom::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        if ($request->filled('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }
        if ($request->filled('is_available') && $request->is_available !== '') {
            $query->where('is_available', $request->is_available);
        }
        if ($request->filled('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }
        if ($request->filled('max_capacity')) {
            $query->where('capacity', '<=', $request->max_capacity);
        }

        $classrooms = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($classrooms);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:classrooms,code',
            'capacity' => 'required|integer|min:1|max:1000',
            'type' => 'required|in:lecture_hall,lab,seminar_room,tutorial_room',
            'equipment' => 'nullable|string',
            'is_available' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $classroom = Classroom::create($request->all());

        return response()->json([
            'message' => 'Classroom created successfully',
            'classroom' => $classroom
        ], 201);
    }

    public function show($id)
    {
        $classroom = Classroom::findOrFail($id);
        return response()->json($classroom);
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:classrooms,code,' . $id,
            'capacity' => 'required|integer|min:1|max:1000',
            'type' => 'required|in:lecture_hall,lab,seminar_room,tutorial_room',
            'equipment' => 'nullable|string',
            'is_available' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $classroom->update($request->all());

        return response()->json([
            'message' => 'Classroom updated successfully',
            'classroom' => $classroom
        ]);
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);
        $classroom->delete();

        return response()->json([
            'message' => 'Classroom deleted successfully'
        ]);
    }

    public function getStats()
    {
        $stats = [
            'total_classrooms' => Classroom::count(),
            'available_classrooms' => Classroom::where('is_available', 1)->count(),
            'total_capacity' => Classroom::sum('capacity'),
            'by_type' => Classroom::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
        ];

        return response()->json($stats);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with('department');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $subjects = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($subjects);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:subjects,code',
            'department_id' => 'required|exists:departments,id',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|in:1st,2nd,3rd,4th,5th',
            'semester' => 'required|in:1st,2nd',
            'credit_hours' => 'nullable|integer|min:0',
            'theory_hours' => 'nullable|integer|min:0',
            'practical_hours' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subject = Subject::create($request->all());

        return response()->json([
            'message' => 'Subject created successfully',
            'subject' => $subject->load('department')
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::with('department')->findOrFail($id);
        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:subjects,code,' . $id,
            'department_id' => 'required|exists:departments,id',
            'program_id' => 'required|exists:programs,id',
            'year' => 'required|in:1st,2nd,3rd,4th,5th',
            'semester' => 'required|in:1st,2nd',
            'credit_hours' => 'nullable|integer|min:0',
            'theory_hours' => 'nullable|integer|min:0',
            'practical_hours' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subject->update($request->all());

        return response()->json([
            'message' => 'Subject updated successfully',
            'subject' => $subject->load('department')
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return response()->json([
            'message' => 'Subject deleted successfully'
        ]);
    }

    public function getDepartmentWiseSubject(Request $request){
        try {
            $query = Subject::query();

            // Filter by department_id if provided
            if ($request->has('department_id') && $request->department_id) {
                $query->where('department_id', $request->department_id);
            }

            // Order by name
            $query->orderBy('name', 'asc');

            // Get active subjects only (optional)
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $subjects = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => $subjects
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading subjects',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

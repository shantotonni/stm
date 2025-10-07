<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::with(['user', 'department', 'designation']);

        if ($request->filled('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('BMDC_NO', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->filled('department_id') && $request->department_id !== '') {
            $query->where('department_id', $request->department_id);
        }

        // Filter by designation
        if ($request->filled('designation_id') && $request->designation_id !== '') {
            $query->where('designation_id', $request->designation_id);
        }

        // Filter by head status
        if ($request->filled('is_head') && $request->is_head !== '') {
            $status = $request->is_head == '1' ? 'Y' : 'N';
            $query->where('is_head', $status);
        }

        $teachers = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($teachers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'BMDC_NO' => 'required|string|max:255|unique:teachers',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:teachers',
            'mobile' => 'required|string|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation_id' => 'nullable|integer',
            'qualification' => 'required|string|max:255',
            'joining_date' => 'required|string|max:255',
            'is_head' => 'boolean',
        ]);

        if ($request->is_head){
            $is_head = 'Y';
        }else{
            $is_head = 'N';
        }

        $teacher = Teacher::create([
            'user_id' => $request->user_id,
            'BMDC_NO' => $request->BMDC_NO,
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'qualification' => $request->qualification,
            'joining_date' => $request->joining_date,
            'is_head' => $is_head,
        ]);

        return response()->json([
            'message' => 'Teacher created successfully',
            'teacher' => $teacher->load(['user', 'department', 'designation'])
        ], 201);
    }

    public function show($id)
    {
        $teacher = Teacher::with(['user', 'department', 'designation'])->findOrFail($id);
        return response()->json($teacher);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'BMDC_NO' => ['required', 'string', 'max:255', Rule::unique('teachers')->ignore($teacher->id)],
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('teachers')->ignore($teacher->id)],
            'mobile' => 'required|string|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation_id' => 'nullable|integer',
            'qualification' => 'required|string|max:255',
            'joining_date' => 'required|string|max:255',
            'is_head' => 'boolean',
        ]);

        if ($request->is_head){
            $is_head = 'Y';
        }else{
            $is_head = 'N';
        }

        $teacher->update([
            'user_id' => $request->user_id,
            'BMDC_NO' => $request->BMDC_NO,
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'qualification' => $request->qualification,
            'joining_date' => $request->joining_date,
            'is_head' => $is_head,
        ]);

        return response()->json([
            'message' => 'Teacher updated successfully',
            'teacher' => $teacher->load(['user', 'department', 'designation'])
        ]);
    }

    public function toggleHead($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->is_head = !$teacher->is_head;
        $teacher->save();

        return response()->json([
            'message' => 'Teacher head status updated successfully',
            'teacher' => $teacher->load(['user', 'department', 'designation'])
        ]);
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();

        return response()->json([
            'message' => 'Teacher deleted successfully'
        ]);
    }

}
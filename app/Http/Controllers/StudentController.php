<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\Student\StudentCollection;
use App\Http\Resources\Student\StudentResource;
use App\Models\Category;
use App\Models\Sessions;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'department']);

        if ($request->filled('search') && $request->search != '') {
            $query->search($request->search);
        }

        if ($request->filled('department_id') && $request->department_id != '') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('year') && $request->year != '') {
            $query->where('year', $request->year);
        }

        if ($request->filled('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $students = $query->paginate($perPage);

        return response()->json($students);
    }

    public function store(StoreStudentRequest $request)
    {
        $category = Category::query()->where('id',$request->category_id)->first();
        $session = Sessions::query()->where('id',$request->session_id)->first();
        try {

            $data = $request->validated();
            $data['category'] = $category->name ? $category->name : '';
            $data['session'] = $session->name ? $session->name : '';

            $student = Student::create($data);
            $student->load(['user', 'department','category','session']);

            return response()->json([
                'message' => 'Student created successfully',
                'data' => $student
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $student = Student::with(['user', 'department'])->findOrFail($id);
            return response()->json(['data' => $student]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Student not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(UpdateStudentRequest $request, $id)
    {
        try {
            $student = Student::findOrFail($id);
            $student->update($request->validated());
            $student->load(['user', 'department']);

            return response()->json([
                'message' => 'Student updated successfully',
                'data' => $student
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $student = Student::findOrFail($id);
            $student->delete();
            return response()->json(['message' => 'Student deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsers()
    {
        $users = User::where('user_type', 'student')
            ->where('is_active', 'Y')
            ->get(['id', 'name', 'login_code']);
        return response()->json($users);
    }

}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentCollection;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Department::with('headTeacher:id,name');
            if ($request->filled('search') && $request->search != '') {
                $query->search($request->search);
            }

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->inactive();
                }
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            if ($request->has('per_page')) {
                $departments = $query->paginate($request->per_page);
            } else {
                $departments = $query->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Departments retrieved successfully',
                'data' => $departments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $department = Department::with('headTeacher:id,name')->find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Department retrieved successfully',
                'data' => $department
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'head_teacher_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|max:10|unique:departments,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ], [
            'name.required' => 'Department name is required',
            'head_teacher_id.required' => 'Head teacher is required',
            'head_teacher_id.exists' => 'Selected head teacher does not exist',
            'code.required' => 'Department code is required',
            'code.unique' => 'Department code already exists'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $department = Department::create([
                'name' => $request->name,
                'head_teacher_id' => $request->head_teacher_id,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'is_active' => $request->is_active ?? true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Department created successfully',
                'data' => $department->load('headTeacher:id,name')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'success' => false,
                'message' => 'Department not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'head_teacher_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|max:10|unique:departments,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ], [
            'name.required' => 'Department name is required',
            'head_teacher_id.required' => 'Head teacher is required',
            'head_teacher_id.exists' => 'Selected head teacher does not exist',
            'code.required' => 'Department code is required',
            'code.unique' => 'Department code already exists'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $department->update([
                'name' => $request->name,
                'head_teacher_id' => $request->head_teacher_id,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'is_active' => $request->is_active ?? $department->is_active
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully',
                'data' => $department->load('headTeacher:id,name')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $department = Department::find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            DB::beginTransaction();

            $department->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $department = Department::find($id);

            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Department not found'
                ], 404);
            }

            DB::beginTransaction();

            $department->is_active = !$department->is_active;
            $department->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Department status updated successfully',
                'data' => $department->load('headTeacher:id,name')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update department status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statistics()
    {
        try {
            $stats = [
                'total' => Department::count(),
                'active' => Department::active()->count(),
                'inactive' => Department::inactive()->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            Department::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Departments deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

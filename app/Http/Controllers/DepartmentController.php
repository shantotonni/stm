<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentCollection;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $department = Department::query()->paginate(15);
        return new DepartmentCollection($department);
    }

    public function store(Request $request)
    {
        try {

            $department = new Department();
            $department->name = $request->name;
            $department->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Department created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'=>400,
                'message'=>$e->getMessage()
            ],400);
        }
    }

    public function update(Request $request,$id)
    {
        try {

            $department = Department::find($id);
            $department->name = $request->name;
            $department->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Department Updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'=>400,
                'message'=>$e->getMessage()
            ],400);
        }
    }

    public function destroy($id)
    {
        Department::where('id',$id)->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Department Deleted successfully'
        ]);
    }

    public function search($query)
    {
        return new DepartmentCollection(Department::where('name','LIKE',"%$query%")->latest()->paginate(20));
    }
}

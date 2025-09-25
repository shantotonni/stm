<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeacherCollection;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $department_id = $request->department_id;
        $BMDC_NO = $request->BMDC_NO;
        $teachers = User::query()
            ->with(['department','designation'])
            ->whereIn('role_id', [4, 2])
            ->when($department_id, function ($query, $department_id) {
                $query->where('department_id', $department_id);
            })
            ->when($BMDC_NO, function ($query, $BMDC_NO) {
                $query->where('BMDC_NO', $BMDC_NO);
            })
            ->orderBy('user_id','desc')->paginate(15);
        return new TeacherCollection($teachers);
    }

    public function store(Request $request)
    {
        try {
            $exist_teacher = User::where('BMDC_NO',$request->BMDC_NO)->exists();
            if ($exist_teacher){
                return response()->json([
                    'status'=>400,
                    'message'=>'Already Added'
                ],400);
            }

            $teachers = new User();
            $teachers->BMDC_NO = $request->BMDC_NO;
            $teachers->name = $request->name;
            $teachers->email = $request->email ? $request->email : '';
            $teachers->password = bcrypt('123456');
            $teachers->mobile = $request->mobile ? $request->mobile : '';
            if ($request->is_head === 'Y'){
                $teachers->role_id = 2;
            }else{
                $teachers->role_id = 4;
            }
            $teachers->department_id = $request->department_id;
            $teachers->designation_id = $request->designation_id;
            $teachers->is_head = $request->is_head;
            $teachers->status = 'Y';
            $teachers->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Student created successfully'
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

            $teachers = User::find($id);
            $teachers->name = $request->name;
            $teachers->mobile = $request->mobile ? $request->mobile : '';
            $teachers->email = $request->email ? $request->email : '';
            $teachers->department_id = $request->department_id;
            $teachers->designation_id = $request->designation_id;
            $teachers->role_id = 4;
            $teachers->is_head = $request->is_head;
            $teachers->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Student created successfully'
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
        $student = Teacher::where('id',$id)->first();
        $student->status = 'N';
        $student->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Student Deleted successfully'
        ]);
    }

    public function search($query)
    {
        return new TeacherCollection(Teacher::where('name','LIKE',"%$query%")->latest()->paginate(20));
    }
}

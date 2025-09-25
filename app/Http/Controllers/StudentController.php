<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StudentRequest;
use App\Http\Resources\Student\StudentCollection;
use App\Http\Resources\Student\StudentResource;
use App\Models\Category;
use App\Models\Sessions;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $sessionId = $request->sessionId;
        $roll_number = $request->roll_number;

        $students = Student::query()->with('session','category');
        if (!empty($sessionId)){
            $students = $students->where('session',$sessionId);
        }
        if (!empty($roll_number)){
            $students = $students->where('roll_no',$roll_number);
        }

        $students = $students->orderBy('student_id','desc')->where('status','Y')->paginate(15);
        return new StudentCollection($students);
    }

    public function store(StudentRequest $request)
    {
        try {
            $session = Sessions::query()->where('session_id',$request->session_id)->first();
            $category = Category::query()->where('id',$request->category_id)->first();
            $exist_student = Student::where('roll_no',$request->roll_no)->where('session',$session->name)->where('student_id_number',$request->student_id_number)->exists();
            if ($exist_student){
                return response()->json([
                    'status'=>400,
                    'message'=>'Already Added'
                ],400);
            }

            $student = new Student();
            $student->name = $request->name;
            $student->roll_no = $request->roll_no;
            $student->batch_number = $request->batch_number;
            $student->email = $request->email;
            $student->mobile = $request->mobile;
            $student->date_of_birth = $request->date_of_birth;
            $student->nid = $request->nid;
            $student->address = $request->address;
            $student->nationality = $request->nationality;
            $student->session = $session->name;
            $student->session_id = $request->session_id;
            $student->category_id = $request->category_id;
            $student->category = $category->name;
            $student->password = bcrypt('123123');
            $student->student_id_number = $request->student_id_number;
            $student->is_hostel = $request->is_hostel;
            $student->status = $request->status;
            $student->is_change_password = 'N';

            $student->save();

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

    public function update(StudentRequest $request,$id)
    {
        try {
            $session = Sessions::query()->where('session_id',$request->session_id)->first();
            $category = Category::query()->where('id',$request->category_id)->first();

            $student = Student::find($id);
            $student->name = $request->name;
            $student->roll_no = $request->roll_no;
            $student->batch_number = $request->batch_number;
            $student->email = $request->email;
            $student->mobile = $request->mobile;
            $student->date_of_birth = $request->date_of_birth;
            $student->nid = $request->nid;
            $student->address = $request->address;
            $student->nationality = $request->nationality;
            $student->session = $session->name;
            $student->session_id = $request->session_id;
            $student->category_id = $request->category_id;
            $student->category = $category->name;
            $student->student_id_number = $request->student_id_number;
            $student->is_hostel = $request->is_hostel;
            $student->status = $request->status;
            $student->is_change_password = 'N';
            $student->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Student Updated successfully'
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
        $student = Student::where('student_id',$id)->first();
        $student->status = 'N';
        $student->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Student Deleted successfully'
        ]);
    }

    public function search($query)
    {
        return new StudentCollection(Student::where('first_name','LIKE',"%$query%")->orWhere('last_name','LIKE',"%$query%")->latest()->paginate(20));
    }

    public function getStudentDetails(Request $request){
        $session_id = $request->session_id;
        $roll_no = $request->roll_no;
        $student = Student::where('session_id',$session_id)->where('roll_no',$roll_no)->with('session','category','category.currency')->first();
        return new StudentResource($student);
    }
}

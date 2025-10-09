<?php

namespace App\Http\Controllers;

use App\Http\Resources\Student\StudentCollection;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\Currency;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EvaluationStatement;
use App\Models\ExamType;
use App\Models\HostelFee;
use App\Models\Menu;
use App\Models\Miscellanious;
use App\Models\Program;
use App\Models\Purpose;
use App\Models\Role;
use App\Models\Sessions;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentBillPayment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Year;
use App\Services\BusinessService;
use App\Services\DepartmentService;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    public function userModalData() {
        return response()->json([
            'status' => 'success',
            'roles' => RoleService::list(),
            'business' => BusinessService::list(),
            'department' => DepartmentService::list(),
            'allSubMenus' => Menu::whereNotIn('MenuID',['Dashboard','Users'])->with('allSubMenus')->get()
        ]);
    }

    public function getAllSession(){
        $sessions = Sessions::where('is_active', 'Y')
            ->get(['id', 'name']);
        return response()->json($sessions);
    }

    public function getAllCategory(){
        $categories = Category::query()->get(['id', 'name']);
        return response()->json($categories);
    }

    public function getAllDepartment(){
        $departments = Department::query()->get(['id','name']);
        return response()->json($departments);
    }

    public function getAllDesignation(){
        return response()->json([
            'status' => 'success',
            'designations' => Designation::all(),
        ]);
    }

    public function getAllSubject(Request $request){
        $query = Subject::query();
        if ($request->filled('department_id') && !empty($request->department_id)) {
            $query->where('department_id', $request->department_id);
        }
        $subjects = $query->get();
        return response()->json($subjects);
    }

    public function getAllExamType(){
        return response()->json(ExamType::all());
    }

    public function getAllStatement(){
        $statements = EvaluationStatement::query()->where('is_active','Y')->orderBy('ordering','asc')->get();
        return response()->json([
           'statements' => $statements
        ]);
    }

    public function getAllRole(){
        return response()->json([
            'status' => 'success',
            'roles' => Role::all(),
        ]);
    }

    public function getAllTeacher(){
        $teachers = Teacher::query()->get();
        return response()->json($teachers);
    }

    public function getAllYear(){
        $years = DB::table('years')->get();
        return response()->json([
           'years' => $years
        ]);
    }

    public function getDepartments()
    {
        $departments = Department::where('is_active', 1)->get(['id', 'name']);
        return response()->json($departments);
    }

    public function getDesignations()
    {
        $designations = Designation::all();
        return response()->json($designations);
    }

    public function getUsers()
    {
        $users = User::where('user_type', 'teacher')
            ->where('is_active', 'Y')
            ->get(['id', 'name', 'login_code']);
        return response()->json($users);
    }

    public function getProgram()
    {
        $programs = Program::query()->get(['id', 'name']);
        return response()->json($programs);
    }

    public function getStudents()
    {
        $students = Student::select('id', 'name', 'roll_no', 'email')->get();
        return response()->json($students);
    }

    public function getTeachersBySubject(Subject $subject): JsonResponse
    {
        $teachers = $subject->teachers()
            ->with('designation:id,name')
            ->select('teachers.id', 'teachers.name', 'teachers.email', 'teachers.designation_id')
            ->orderBy('teachers.name', 'asc')
            ->get();
        return response()->json($teachers, 200);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        try {
            $teacher->load(['department', 'subjects']);

            return response()->json([
                'success' => true,
                'data' => $teacher,
                'message' => 'Teacher details fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teacher details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllClassRoom(){
        $class_room = Classroom::query()->where('is_available',1)->get();
        return response()->json($class_room);
    }

}

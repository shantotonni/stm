<?php

namespace App\Http\Controllers;

use App\Http\Resources\Student\StudentCollection;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EvaluationStatement;
use App\Models\HostelFee;
use App\Models\Menu;
use App\Models\Miscellanious;
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
        return response()->json([
            'status' => 'success',
            'sessions' => Sessions::all(),
        ]);
    }

    public function getAllCategory(){
        return response()->json([
            'status' => 'success',
            'categories' => Category::all(),
        ]);
    }

    public function getAllDepartment(){
        return response()->json([
            'status' => 'success',
            'departments' => Department::all(),
        ]);
    }

    public function getAllDesignation(){
        return response()->json([
            'status' => 'success',
            'designations' => Designation::all(),
        ]);
    }

    public function getAllSubject(){
        return response()->json([
            'status' => 'success',
            'subjects' => Subject::all(),
        ]);
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
        $teachers = User::query()->where('role_id',4)->get();
        return response()->json([
           'teachers' => $teachers
        ]);
    }

    public function getAllYear(){
        $years = DB::table('years')->get();
        return response()->json([
           'years' => $years
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UserRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\User\UserCollection;
use App\Models\Advances;
use App\Models\Menu;
use App\Models\SubMenu;
use App\Models\SubMenuPermission;
use App\Models\User;
use App\Models\UserBusiness;
use App\Models\UserDepartment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $users = User::with('role')->where('role_id','!=',4)->orderBy('created_at','desc')->paginate(15);
        return new UserCollection($users);
    }

    public function store(UserRequest $request){

        if ($request->has('avater')) {
            $image = $request->avater;
            $name = uniqid().time().'.' . explode('/', explode(':', substr($image, 0, strpos($image, ';')))[1])[1];
            Image::make($image)->save(public_path('images/user/').$name);
        } else {
            $name = 'not_found.jpg';
        }

        $user = new User();
        $user->name = $request->name;
        $user->role_id = $request->role_id;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->BMDC_NO = $request->user_unique_code;
        $user->avatar = $name;
        $user->password = bcrypt($request->password);
        $user->status = 'Y';
        $user->is_head = 'N';
        $user->save();

        return response()->json(['message'=>'User Created Successfully'],200);
    }


    public function update(UserUpdateRequest $request, $id){

        $user = User::where('user_id',$request->user_id)->first();
        $image = $request->avater;

        if ($image != $user->avater) {
            if ($request->has('avater')) {
                //code for remove old file
                if ($user->avater != '' && $user->avater != null) {
                    $destinationPath = 'images/user/';
                    $file_old = $destinationPath . $user->avater;
                    if (file_exists($file_old)) {
                        unlink($file_old);
                    }
                }
                $name = uniqid() . time() . '.' . explode('/', explode(':', substr($image, 0, strpos($image, ';')))[1])[1];
                Image::make($image)->resize(1600,1000)->save(public_path('images/user/') . $name);
            } else {
                $name = $user->avater;
            }
        }else{
            $name = $user->avater;
        }

        $user->name = $request->name;
        $user->role_id = $request->role_id;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->BMDC_NO = $request->user_unique_code;
        $user->avatar = $name;
        $user->status = 'Y';
        $user->save();
        return response()->json(['message'=>'User Updated Successfully'],200);
    }

    public function updatePassword(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'staffId' => 'required|string',
                'password' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()], 400);
            }
            $user = User::find($request->staffId);
            $user->Password = bcrypt($request->password);
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Password Updated Successfully'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ],500);
        }
    }

    public function delete($id)
    {
        if (false) {
            return response()->json(['message' => "User is already used!"], 500);
        } else {
            User::where('id', $id)->delete();
            return response()->json(['message' => "User deleted successfully"]);
        }
    }

    public function getUserInfo($staffId)
    {
        $user = User::where('StaffID',$staffId)->with(['roles','userBusiness.business','userDepartment.department','business','userSubmenu'])->first();
        $allSubMenus = Menu::whereNotIn('MenuID',['Dashboard','Users'])->with('allSubMenus')->get();
        return response()->json([
            'status' => 'success',
            'data' => $user,
            'allSubMenus' => $allSubMenus
        ]);
    }

    public function hrData(Request $request)
    {
        $query = DB::connection('hr_db')->select("SELECT P.EmpCode, P.Name, D.DesgName, De.DeptName 
        FROM Personal P	
            INNER JOIN Employer E
                ON P.EmpCode = E.EmpCode
            INNER JOIN Designation D
                ON E.DesgCode = D.DesgCode
            INNER JOIN Department DE
                ON E.DeptCode = DE.DeptCode
        WHERE E.EmpCode = '$request->staffId'");
        if (count($query)) {
            $data = $query[0];
        } else {
            $data = [];
        }
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function loadUsersHR(Request $request)
    {
        $users = Advances::select('ResStaffID as EmpCode','ResStaffName as Name','ResStaffDesignation as DesgName','ResStaffDepartment as DeptName','ResStaffEmail as Email','ResStaffMobile as Mobile','BankID','BranchName','RoutingNo')
            ->where('ResStaffID',$request->staffId)->first();
        if (!$users) {
            $query = DB::connection('hr_db')->select("SELECT P.EmpCode, P.Name, D.DesgName, De.DeptName 
        FROM Personal P	
            INNER JOIN Employer E
                ON P.EmpCode = E.EmpCode
            INNER JOIN Designation D
                ON E.DesgCode = D.DesgCode
            INNER JOIN Department DE
                ON E.DeptCode = DE.DeptCode
        WHERE E.EmpCode = '$request->staffId'");
            if (count($query)) {
                $users = $query[0];
            } else {
                $users = [];
            }
        }
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function getAllUser(){
        return new UserCollection(User::all());
    }
}

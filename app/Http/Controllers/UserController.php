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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('login_code', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== '') {
            $status = $request->status == '1' ? 'Y' : 'N';
            $query->where('is_active', $status);
        }

        if ($request->filled('user_type') && $request->user_type !== '') {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('role_id') && $request->role_id !== '') {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'login_code' => 'required|string|max:100|unique:users',
            'name' => 'required|string|max:100',
            'password' => 'required|string|min:6',
            'mobile' => 'nullable|string|max:20',
            'role_id' => 'required|integer',
            'user_type' => 'required|in:admin,teacher,student,dept_head',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'is_change_password' => 'boolean',
        ]);

        $data = [
            'login_code' => $request->login_code,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'mobile' => $request->mobile,
            'role_id' => $request->role_id,
            'user_type' => $request->user_type,
            'is_active' => $request->is_active ?? true,
            'is_change_password' => $request->is_change_password ?? false,
        ];

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '_' . $avatar->getClientOriginalName();
            $avatar->move(public_path('uploads/avatars'), $avatarName);
            $data['avatar'] = 'uploads/avatars/' . $avatarName;
        }

        $user = User::create($data);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('role')
        ], 201);
    }

    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'login_code' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user->id)],
            'name' => 'required|string|max:100',
            'password' => 'nullable|string|min:6',
            'mobile' => 'nullable|string|max:20',
            'role_id' => 'required|integer',
            'user_type' => 'required|in:admin,teacher,student,dept_head',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'is_change_password' => 'boolean',
        ]);


        $data = [
            'login_code' => $request->login_code,
            'name' => $request->name,
            'mobile' => $request->mobile,
            'role_id' => $request->role_id,
            'user_type' => $request->user_type,
            'is_active' => $request->is_active ? 'Y' : 'N',
            'is_change_password' => 'N',
        ];

        // Update password if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $avatar = $request->file('avatar');
            $avatarName = time() . '_' . $avatar->getClientOriginalName();
            $avatar->move(public_path('uploads/avatars'), $avatarName);
            $data['avatar'] = 'uploads/avatars/' . $avatarName;
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('role')
        ]);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user->load('role')
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->avatar && file_exists(public_path($user->avatar))) {
            unlink(public_path($user->avatar));
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    // Get all roles for dropdown
    public function getRoles()
    {
        $roles = \App\Models\Role::with('menus')->get();
        return response()->json($roles);
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

    public function getAllUser(){
        return new UserCollection(User::all());
    }
}

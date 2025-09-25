<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentAuthController extends Controller
{
    function __construct() {
        Config::set('jwt.user', Student::class);
        Config::set('auth.providers', ['users' => [
            'driver' => 'eloquent',
            'model' => Student::class,
        ]]);
    }

    public function studentLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'studentID' => 'required|string',
            'password'  => 'required|string|min:6',
        ]);


        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid'], 400);
        }
        if ($token = JWTAuth::attempt(['student_id_number' => $request->studentID,'password' => $request->password])) {
            return response()->json([
                'token' => $token,
                'user' => Auth::user()
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Email or Password!'
            ],500);
        }
    }

    public function changePassword(Request $request){

        $request->validate([
            'oldPassword'      => ['required', 'min:6'],
            'newPassword'      => ['required', 'min:6', 'confirmed'],
        ]);

        $student = Auth::user();
        if (!Hash::check($request->oldPassword, $student->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Old password does not match.'
            ], 422);
        }

        $student->password = Hash::make($request->newPassword);
        $student->is_change_password = 'Y';
        $student->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.'
        ]);
    }

    public function me()
    {
        return response()->json($this->guard()->user());

    }

    public function logout()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            //UserLog::create(['UserId' => $user->ID, 'TransactionTime' => Carbon::now(), 'TransactionDetails' => "Logged Out"]);
            $this->guard()->logout();
        } catch (\Exception $exception) {

        }
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }

    public function guard()
    {
        return Auth::guard('api');
    }

}

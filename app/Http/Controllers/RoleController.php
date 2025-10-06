<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        try {
            $roles = Role::with(['permissions', 'menus'])->get();

            return response()->json([
                'success' => true,
                'roles' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch roles'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::with(['permissions', 'menus', 'users'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'role' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Role not found'
            ], 404);
        }
    }
}

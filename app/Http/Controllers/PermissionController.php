<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Permission::query();
            if ($request->has('module')) {
                $query->where('module', $request->module);
            }

            $permissions = $query->orderBy('module')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch permissions'
            ], 500);
        }
    }

    public function grouped()
    {
        try {
            $permissions = Permission::orderBy('module')
                ->orderBy('name')
                ->get();

            // Group by module
            $grouped = $permissions->groupBy('module');

            return response()->json([
                'success' => true,
                'permissions' => $grouped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch permissions'
            ], 500);
        }
    }

}

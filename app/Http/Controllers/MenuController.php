<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\MenuRequest;
use App\Http\Resources\Menu\MenuCollection;
use App\Http\Resources\Menu\MenuResource;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\UserMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // Get user's accessible menus
    public function getUserMenus()
    {
        $user = auth()->user();
        $menus = $user->getAccessibleMenus();

        return response()->json([
            'success' => true,
            'menus' => $menus
        ]);
    }

    public function index()
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $menus = Menu::with(['parent', 'children', 'roles'])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['menus' => $menus]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:menus',
            'title' => 'required|string',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'permission' => 'nullable|string|exists:permissions,name',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $menu = Menu::create($request->only([
            'name', 'title', 'icon', 'route', 'url',
            'parent_id', 'permission', 'sort_order'
        ]));

        // Attach roles if provided
        if ($request->has('role_ids')) {
            $menu->roles()->attach($request->role_ids);
        }

        return response()->json([
            'menu' => $menu->load('roles'),
            'message'=>'Menu Item Created Successfully'
        ], 201);
    }

    public function edit($id)
    {
        $menu = Menu::where('MenuID',$id)->first();
        return new MenuResource($menu);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $menu = Menu::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:menus,name,' . $id,
            'title' => 'string',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'permission' => 'nullable|string|exists:permissions,name',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $menu->update($request->only([
            'name', 'title', 'icon', 'route', 'url',
            'parent_id', 'permission', 'sort_order', 'is_active'
        ]));

        if ($request->has('role_ids')) {
            $menu->roles()->sync($request->role_ids);
        }

        return response()->json(['menu' => $menu->load('roles')]);
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $menu = Menu::findOrFail($id);

        if ($menu->children()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete menu with children'
            ], 400);
        }

        $menu->delete();
        return response()->json(['message' => 'Menu deleted successfully']);
    }

    public function getRoleMenus()
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roles = Role::with('menus')->get();
        return response()->json(['roles' => $roles]);
    }

    public function updateRoleMenus(Request $request, $roleId)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $role = Role::findOrFail($roleId);

        $validator = Validator::make($request->all(), [
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'exists:menus,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role->menus()->sync($request->menu_ids);

        return response()->json([
            'message' => 'Role menus updated successfully',
            'role' => $role->load('menus')
        ]);
    }
}

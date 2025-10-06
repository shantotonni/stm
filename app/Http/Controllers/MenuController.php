<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\MenuRequest;
use App\Http\Resources\Menu\MenuCollection;
use App\Http\Resources\Menu\MenuResource;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{

    // Get user's accessible menus
    public function getUserMenus()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized'
                ], 401);
            }

            $menus = Menu::getMenuTree($user->id);

            return response()->json([
                'success' => true,
                'menus' => $menus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch user menus',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $menus = Menu::with(['roles', 'parent'])
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'menus' => $menus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch menus',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:menu|max:255',
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:255',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menu,id',
            'permission' => 'nullable|string|exists:permissions,name',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create menu
            $menu = Menu::create([
                'name' => $request->name,
                'title' => $request->title,
                'icon' => $request->icon,
                'route' => $request->route,
                'url' => $request->url,
                'parent_id' => $request->parent_id,
                'permission' => $request->permission,
                'sort_order' => $request->sort_order ?? 0,
                'is_active' => $request->is_active ? 'Y' : 'N'
            ]);

            if ($request->has('role_ids') && is_array($request->role_ids)) {
                $menu->roles()->attach($request->role_ids);
            }

            $permission = Permission::query()->where('name',$request->permission)->first();

            if ($request->has('role_ids') && is_array($request->role_ids)) {
                $permission->roles()->attach($request->role_ids);
            }

            DB::commit();
            $menu->load('roles', 'parent');

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully',
                'menu' => $menu
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to create menu',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $menu = Menu::with(['roles', 'parent', 'children'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'menu' => $menu
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Menu not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Find menu
        $menu = Menu::findOrFail($id);

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'string|unique:menu,name,' . $id . '|max:255',
            'title' => 'string|max:255',
            'icon' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:255',
            'url' => 'string',
            'parent_id' => 'nullable|exists:menu,id',
            'permission' => 'nullable|string|exists:permissions,name',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if trying to set self as parent
        if ($request->has('parent_id') && $request->parent_id == $id) {
            return response()->json([
                'success' => false,
                'error' => 'Menu cannot be its own parent'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $menu->update($request->only([
                'name', 'title', 'icon', 'route', 'url',
                'parent_id', 'permission', 'sort_order', 'is_active'
            ]));

            if ($request->has('role_ids')) {
                if (is_array($request->role_ids)) {
                    $menu->roles()->sync($request->role_ids);
                } else {
                    $menu->roles()->detach();
                }
            }

            DB::commit();

            $menu->load('roles', 'parent');

            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully',
                'menu' => $menu
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to update menu',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasPermission('manage_menus')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $menu = Menu::findOrFail($id);
            if ($menu->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete menu that has child menus. Please delete children first.'
                ], 400);
            }
            $menu->delete();
            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete menu',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRoleMenus()
    {
        try {
            $roles = Role::with('menus')->get();

            return response()->json([
                'success' => true,
                'roles' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch role menus'
            ], 500);
        }
    }

    public function updateRoleMenus(Request $request, $roleId)
    {

        $validator = Validator::make($request->all(), [
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'exists:menu,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::findOrFail($roleId);

            // Sync menus to role
            $role->menus()->sync($request->menu_ids);

            // Return updated role with menus
            $role->load('menus');

            return response()->json([
                'success' => true,
                'message' => 'Role menus updated successfully',
                'role' => $role
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update role menus',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.sort_order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->menus as $menuData) {
                Menu::where('id', $menuData['id'])
                    ->update(['sort_order' => $menuData['sort_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Menu order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Failed to reorder menus'
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $menu = Menu::findOrFail($id);
            $menu->is_active = !$menu->is_active;
            $menu->save();

            return response()->json([
                'success' => true,
                'message' => 'Menu status updated',
                'menu' => $menu
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle status'
            ], 500);
        }
    }

}

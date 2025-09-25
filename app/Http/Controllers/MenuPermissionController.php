<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\UserMenu;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class MenuPermissionController extends Controller
{
    public function getUserMenuPermission($userId)
    {
        $data['menu'] = Menu::select('MenuID', 'Name')->with(['MenuItem' => function ($item) {
            $item->select('Id', 'MenuID', 'Name');
        }])->orderBy('Ordering', 'asc')->get();

        $data['usermenu'] = UserMenu::where('UserId', $userId)->pluck('MenuItemId');

        return response()->json([
            'data' =>$data
        ],200);
    }

    public function saveUserMenuPermission(Request $request)
    {
        $userId = $request->userId;
        $permission = $request->permission;
        $sortedPerm = [];
        foreach ($permission as $key => $value) {
            if ($value) array_push($sortedPerm, $key);
        }

        $current = UserMenu::where('UserId', $userId)->pluck('MenuItemId')->toArray();
        $inserted = array_diff($sortedPerm, $current);
        foreach ($inserted as $item) {
            UserMenu::create(['UserId' => $userId, 'MenuItemId' => $item]);
        }
        $remove = array_diff($current, $sortedPerm);
        UserMenu::where('UserId', $userId)->whereIn('MenuItemId', $remove)->delete();

        return response()->json(['message' => "Menu permissions updated Successfully"]);
    }

    public function getSidebarAllUserMenu(){
        $user = JWTAuth::parseToken()->authenticate();

        $menuId = MenuItem::join('usermenu', 'usermenu.MenuItemId', 'menuitem.Id')
            ->where('usermenu.UserId', $user->user_id)
            ->pluck('menuitem.MenuID');
        // return $menuId;

        $menuItemId = MenuItem::join('usermenu', 'usermenu.MenuItemId', 'menuitem.Id')
            ->where('usermenu.UserId', $user->user_id)
            ->pluck('menuitem.Id');

        $menu = Menu::whereIn('MenuID', $menuId)->where('Status', 'Y')
            ->orderBy('Ordering', 'asc')->with(['menuItem' => function ($item) use ($menuItemId) {
                $item->whereIn('Id', $menuItemId)->where('Status', 'Y');
            }])->get();
        return response()->json(['user_menu'=> $menu],200);
    }
}

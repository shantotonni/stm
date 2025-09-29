<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Menu extends Model
{
    use HasFactory;
    protected $table = "menu";
    public $primaryKey = 'id';
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order');
    }

    public function menuItem(){
        return $this->hasMany(MenuItem::class,'MenuID','MenuID');
    }

    // Roles that have access to this menu
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_permissions');
    }

    // Check if user has access to this menu
    public function hasAccess($user)
    {
        // If no permission required, allow access
        if (!$this->permission) {
            return true;
        }

        // Check if user has the required permission
        return $user->hasPermission($this->permission);
    }

    // Get menu tree structure
    public static function getMenuTree($userId = null)
    {
        $user = $userId ? User::find($userId) : auth()->user();

        if (!$user) {
            return [];
        }

        $menus = self::where('is_active', 'Y')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return $menus->filter(function($menu) use ($user) {
            return $menu->hasAccess($user);
        })->map(function($menu) use ($user) {
            return $menu->formatForResponse($user);
        })->values();
    }

    // Format menu for API response
    public function formatForResponse($user)
    {
        $children = $this->children->filter(function($child) use ($user) {
            return $child->hasAccess($user);
        })->map(function($child) use ($user) {
            return $child->formatForResponse($user);
        })->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->url,
            'children' => $children,
            'has_children' => $children->count() > 0
        ];
    }



}

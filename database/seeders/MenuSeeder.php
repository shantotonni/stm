<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Main Dashboard
        $dashboard = Menu::create([
            'name' => 'dashboard',
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'route' => '/dashboard',
            'url' => '/dashboard',
            'sort_order' => 1
        ]);

        // Users Management
        $users = Menu::create([
            'name' => 'users',
            'title' => 'Users Management',
            'icon' => 'fas fa-users',
            'sort_order' => 2,
            'permission' => 'view_users'
        ]);

        Menu::create([
            'name' => 'users-list',
            'title' => 'All Users',
            'route' => '/users',
            'url' => '/users-list',
            'parent_id' => $users->id,
            'permission' => 'view_users',
            'sort_order' => 1
        ]);

        Menu::create([
            'name' => 'users-create',
            'title' => 'Add User',
            'route' => '/users/create',
            'url' => '/users-create',
            'parent_id' => $users->id,
            'permission' => 'create_users',
            'sort_order' => 2
        ]);

        // Students Management
        $students = Menu::create([
            'name' => 'students',
            'title' => 'Students',
            'icon' => 'fas fa-user-graduate',
            'sort_order' => 3,
            'permission' => 'view_students'
        ]);

        Menu::create([
            'name' => 'students-list',
            'title' => 'All Students',
            'route' => '/students',
            'url' => '/students-list',
            'parent_id' => $students->id,
            'permission' => 'view_students',
            'sort_order' => 1
        ]);

        // Teachers Management
        $teachers = Menu::create([
            'name' => 'teachers',
            'title' => 'Teachers',
            'icon' => 'fas fa-chalkboard-teacher',
            'sort_order' => 4,
            'permission' => 'view_teachers'
        ]);

        Menu::create([
            'name' => 'teachers-list',
            'title' => 'All Teachers',
            'route' => '/teachers',
            'url' => '/teachers-list',
            'parent_id' => $teachers->id,
            'permission' => 'view_teachers',
            'sort_order' => 1
        ]);

        // Courses
        $subjects = Menu::create([
            'name' => 'subject-list',
            'title' => 'Subject',
            'icon' => 'fas fa-book',
            'route' => '/subjects',
            'url' => '/subjects-list',
            'sort_order' => 5,
            'permission' => 'view_courses'
        ]);

        // Profile
        $profile = Menu::create([
            'name' => 'profile',
            'title' => 'My Profile',
            'icon' => 'fas fa-user',
            'route' => '/profile',
            'url' => '/profile',
            'sort_order' => 6,
            'permission' => 'view_own_profile'
        ]);

        // System Settings (Admin only)
        $settings = Menu::create([
            'name' => 'settings',
            'title' => 'System Settings',
            'icon' => 'fas fa-cog',
            'sort_order' => 7,
            'permission' => 'manage_system'
        ]);

        Menu::create([
            'name' => 'menu-management',
            'title' => 'Menu Management',
            'route' => '/admin/menus',
            'url' => '/admin/menus-list',
            'parent_id' => $settings->id,
            'permission' => 'manage_menus',
            'sort_order' => 1
        ]);

        // Assign menus to roles
        $adminRole = Role::where('name', 'admin')->first();
        $headRole = Role::where('name', 'dept_head')->first();
        $teacherRole = Role::where('name', 'teacher')->first();
        $studentRole = Role::where('name', 'student')->first();

        if ($adminRole) {
            $adminRole->menus()->attach(Menu::all());
        }

        if ($headRole) {
            $headMenus = Menu::whereIn('name', [
                'dashboard', 'students', 'students-list', 'subject-list', 'profile'
            ])->get();
            $teacherRole->menus()->attach($headMenus);
        }

        if ($teacherRole) {
            $teacherMenus = Menu::whereIn('name', [
                'dashboard', 'students', 'students-list', 'subject-list', 'profile'
            ])->get();
            $teacherRole->menus()->attach($teacherMenus);
        }

        if ($studentRole) {
            $studentMenus = Menu::whereIn('name', [
                'dashboard', 'subject-list', 'profile'
            ])->get();
            $studentRole->menus()->attach($studentMenus);
        }
    }
}

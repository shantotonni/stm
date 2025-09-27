<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RBACSeeder extends Seeder
{
    public function run()
    {
        // Create Permissions
        $permissions = [
            // Users
            ['name' => 'view_users', 'display_name' => 'View Users', 'module' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'module' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'module' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'module' => 'users'],

            // Students
            ['name' => 'view_students', 'display_name' => 'View Students', 'module' => 'students'],
            ['name' => 'create_students', 'display_name' => 'Create Students', 'module' => 'students'],
            ['name' => 'edit_students', 'display_name' => 'Edit Students', 'module' => 'students'],
            ['name' => 'delete_students', 'display_name' => 'Delete Students', 'module' => 'students'],

            // Teachers
            ['name' => 'view_teachers', 'display_name' => 'View Teachers', 'module' => 'teachers'],
            ['name' => 'create_teachers', 'display_name' => 'Create Teachers', 'module' => 'teachers'],
            ['name' => 'edit_teachers', 'display_name' => 'Edit Teachers', 'module' => 'teachers'],
            ['name' => 'delete_teachers', 'display_name' => 'Delete Teachers', 'module' => 'teachers'],

            // subject
            ['name' => 'view_subject', 'display_name' => 'View Subject', 'module' => 'subject'],
            ['name' => 'create_subject', 'display_name' => 'Create Subject', 'module' => 'subject'],
            ['name' => 'edit_subject', 'display_name' => 'Edit Subject', 'module' => 'subject'],
            ['name' => 'delete_subject', 'display_name' => 'Delete Subject', 'module' => 'subject'],

            // Profile
            ['name' => 'view_own_profile', 'display_name' => 'View Own Profile', 'module' => 'profile'],
            ['name' => 'edit_own_profile', 'display_name' => 'Edit Own Profile', 'module' => 'profile'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create Roles
        $adminRole = Role::query()->where('id',1)->first();

        $departmentHeadRole = Role::query()->where('id',2)->first();

        $teacherRole = Role::query()->where('id',4)->first();

        $studentRole = Role::query()->where('id',5)->first();

        // Assign Permissions to Roles
        $adminPermissions = Permission::all();
        $adminRole->permissions()->attach($adminPermissions);

        $headPermissions = Permission::whereIn('name', [
            'view_students', 'view_subject', 'create_subject', 'edit_subject',
            'view_own_profile', 'edit_own_profile'
        ])->get();
        $departmentHeadRole->permissions()->attach($headPermissions);

        $teacherPermissions = Permission::whereIn('name', [
            'view_students', 'view_subject', 'create_subject', 'edit_subject',
            'view_own_profile', 'edit_own_profile'
        ])->get();
        $teacherRole->permissions()->attach($teacherPermissions);

        $studentPermissions = Permission::whereIn('name', [
            'view_subject', 'view_own_profile', 'edit_own_profile'
        ])->get();
        $studentRole->permissions()->attach($studentPermissions);
    }
}
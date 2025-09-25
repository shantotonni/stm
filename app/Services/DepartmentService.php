<?php

namespace App\Services;

use App\Models\Advances;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class DepartmentService
{
    public static function list() {
        return Department::select(DB::raw("CONCAT(DeptName,'(',DeptCode,')') AS Department"),'DeptCode')->get();
    }

    public static function departments() {
        return Advances::select(DB::raw("distinct ResStaffDepartment"))->get();
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectControllerAlternative extends Controller
{
    public function getTeachersBySubjectAlternative(Subject $subject): JsonResponse
    {
        try {
            // Get all teachers from the same department
            $teachers = Teacher::where('department_id', $subject->department_id)
                ->where('status', 'active')
                ->select('id', 'name', 'email', 'designation')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $teachers,
                'message' => 'Teachers fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teachers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

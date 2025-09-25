<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\TeacherEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class StudentDashboardController extends Controller
{
    function __construct() {
        Config::set('jwt.user', Student::class);
        Config::set('auth.providers', ['users' => [
            'driver' => 'eloquent',
            'model' => Student::class,
        ]]);
    }

    public function dashboard()
    {
        $student = auth()->user();
        $studentId = auth()->id();

        $totalSurveys = TeacherEvaluation::with('teacher')
            ->where('student_id', $studentId)
            ->orderBy('submitted_date', 'desc')
            ->get();

        // Todays survey status
        $todaySurvey = TeacherEvaluation::with('teacher')
            ->where('student_id', $studentId)
            ->whereDate('submitted_date', now()->toDateString())
            ->get();

        // Recent surveys (last 7)
        $recentSurveys = TeacherEvaluation::with('teacher')
            ->where('student_id', $studentId)
            ->orderBy('submitted_date', 'desc')
            ->take(7)
            ->get();

        return response()->json([
            'is_today_submitted' => empty($todaySurvey) ? true : false,
            'today_submitted_count' => count($todaySurvey),
            'total_survey'   => count($totalSurveys),
            'today_survey'   => $todaySurvey->map(function ($s){
                return [
                    'id' => $s->id,
                    'date' => date('Y-m-d',strtotime($s->submitted_date)),
                    'teacher_name' => $s->teacher->name,
                    'phase' => $s->student_phase,
                    'department' => isset($s->teacher->department) ? $s->teacher->department->name : '',
                    'submitted' => true
                ];
            }),
            'last_seven_surveys'  => $recentSurveys->map(function($s){
                return [
                    'id' => $s->id,
                    'date' => date('Y-m-d',strtotime($s->submitted_date)),
                    'teacher_name' => $s->teacher->name,
                    'phase' => $s->student_phase,
                    'department' => isset($s->teacher->department) ? $s->teacher->department->name : '',
                    'submitted' => true
                ];
            })
        ]);
    }
}

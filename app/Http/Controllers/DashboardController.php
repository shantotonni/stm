<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboardData(){
//        $totalTeachers = DB::table('teachers')->count();
//        $totalStudents = DB::table('students')->count();
//        $totalSurveys = DB::table('teacher_evaluations')->count();
//
//        $submittedStudents = DB::table('teacher_evaluations')
//            ->distinct()
//            ->pluck('student_id')
//            ->toArray();
//
//        $pendingSurveys = DB::table('students')
//            ->whereNotIn('student_id', $submittedStudents)
//            ->count();

        return response()->json([
            'totalTeachers'     => [],
            'totalStudents'     => [],
            'totalSurveys'      => [],
            'pendingSurveys'    => [],
        ]);
    }

    public function topFiveTeacher(){
//        $topTeachers = DB::table('teacher_evaluations as te')
//            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
//            ->join('teachers as t', 'te.teacher_id', '=', 't.id')
//            ->select(
//                't.id as teacher_id',
//                't.name as teacher_name',
//                DB::raw('ROUND(AVG(ted.rating), 2) as avg_score'),
//                DB::raw('COUNT(DISTINCT te.student_id) as total_students_surveyed')
//            )
//            ->groupBy('t.id', 't.name')
//            ->orderByDesc('avg_score')
//            ->limit(5)
//            ->get();

        return response()->json([
            'topTeachers'     => [],
        ]);
    }

    public function lowestFiveTeacher(){
//        $lowTeachers = DB::table('teacher_evaluations as te')
//            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
//            ->join('teachers as t', 'te.teacher_id', '=', 't.id')
//            ->select(
//                't.id as teacher_id',
//                't.name as teacher_name',
//                DB::raw('ROUND(AVG(ted.rating), 2) as avg_score'),
//                DB::raw('COUNT(DISTINCT te.student_id) as total_students_surveyed')
//            )
//            ->groupBy('t.id', 't.name')
//            ->orderBy('avg_score', 'asc')
//            ->limit(5)
//            ->get();

        return response()->json([
            'lowTeachers'     => [],
        ]);
    }

    public function getChartData(){
//        $fromDate = $request->from_date ?? null;
//        $toDate   = $request->to_date ?? null;
//
//        // Base query with optional date filter
//        $dateFilter = function ($query) use ($fromDate, $toDate) {
//            if ($fromDate && $toDate) {
//                $query->whereBetween('submitted_date', [$fromDate, $toDate]);
//            }
//        };
//
//        //Teacher Wise Average Rating
//        $teacherWiseRating = DB::table('teacher_evaluations as te')
//            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
//            ->join('teachers as t', 'te.teacher_id', '=', 't.id')
//            ->when($fromDate && $toDate, $dateFilter)
//            ->select(
//                't.name as teacher_name',
//                DB::raw('ROUND(AVG(ted.rating), 2) as avg_score')
//            )
//            ->groupBy('t.name')
//            ->orderByDesc('avg_score')
//            ->get();
//
//        //Phase Wise Survey Submission
//        $phaseWiseSurvey = DB::table('teacher_evaluations')
//            ->when($fromDate && $toDate, $dateFilter)
//            ->select('student_phase', DB::raw('COUNT(*) as total_surveys'))
//            ->groupBy('student_phase')
//            ->get();
//
//        //Monthly Survey Submission Trend
//        $monthlySurveys = DB::table('teacher_evaluations')
//            ->when($fromDate && $toDate, $dateFilter)
//            ->select(
//                DB::raw('DATE_FORMAT(submitted_date, "%Y-%m") as month'),
//                DB::raw('COUNT(*) as total_surveys')
//            )
//            ->groupBy('month')
//            ->orderBy('month')
//            ->get();
//
//        //Rating Distribution
//        $ratingDistribution = DB::table('teacher_evaluations_details')
//            ->select('rating', DB::raw('COUNT(*) as total'))
//            ->groupBy('rating')
//            ->orderBy('rating')
//            ->get();
//
//        //Student Participation
//        $totalStudents = DB::table('students')->count();
//        $submittedStudents = DB::table('teacher_evaluations')
//            ->when($fromDate && $toDate, $dateFilter)
//            ->distinct()
//            ->count('student_id');
//        $pendingStudents = $totalStudents - $submittedStudents;
//
//        $studentParticipation = [
//            'submitted' => $submittedStudents,
//            'pending'   => $pendingStudents,
//        ];

        return response()->json([
            'teacherWiseRating'   => [],
            'phaseWiseSurvey'     => [],
            'monthlySurveys'      => [],
            'ratingDistribution'  => [],
            'studentParticipation'=> [],
        ]);
    }
}

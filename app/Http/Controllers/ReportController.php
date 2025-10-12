<?php

namespace App\Http\Controllers;

use App\Models\Student;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function teacherWiseAverageRating(Request $request){

        $from_date = request()->get('from_date');
        $to_date   = request()->get('to_date');

        $user = auth()->user();

        $query = DB::table('teacher_evaluations as te')
            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
            ->join('users as t', 'te.teacher_id', '=', 't.user_id')
            ->join('departments as d', 'd.id', '=', 't.department_id')
            ->join('designations as dd', 'dd.id', '=', 't.designation_id')
            ->select(
                't.user_id as teacher_id',
                't.BMDC_NO',
                't.name as teacher_name',
                'd.name as department_name',
                'dd.name as designation_name',

                //total score
                DB::raw('(SUM(ted.rating)-1) as total_score'),

                //total survey
                DB::raw('COUNT(distinct te.id) as total_surveys'),

                //Average = total_score ÷ total_surveys
                DB::raw('ROUND((SUM(ted.rating) / NULLIF(COUNT(distinct te.id),0)- 1), 0) as avg_score')
            )
            ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                $query->whereBetween('te.submitted_date', [$from_date, $to_date]);
            });
        if ($user->role_id === 4) {
            $query->where('te.teacher_id', $user->user_id);
        } elseif ($user->is_head === 'Y') {
            $query->where('t.department_id', $user->department_id);
        }

        $teacherReport = $query->groupBy('t.user_id','t.BMDC_NO', 't.name','d.name','dd.name')
            ->orderByDesc('avg_score')
            ->paginate(10);

        return response()->json([
            'teacherReport' => $teacherReport
        ]);
    }

    public function studentWiseParticipationReport(Request $request){
        $studentReport = DB::table('teacher_evaluations as te')
            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
            ->select(
                'te.student_id',
                'te.student_name',
                'te.roll_no',
                DB::raw('COUNT(DISTINCT te.id) as total_surveys'),
                DB::raw('ROUND(AVG(ted.rating), 2) as avg_score')
            )
            ->groupBy('te.student_id', 'te.student_name', 'te.roll_no')
            ->orderBy('te.student_name')
            ->get();
        return response()->json([
            'studentReport' => $studentReport
        ]);
    }

    public function questionWiseAnalysis(){
        $questionReport = DB::table('teacher_evaluations_details as ted')
            ->join('evaluation_statements as q', 'ted.evaluation_statement_id', '=', 'q.id')
            ->select(
                'q.id as question_id',
                'q.statement as question',
                DB::raw('ROUND(AVG(ted.rating), 2) as avg_score'),
            )
            ->groupBy('q.id', 'q.statement')
            ->orderBy('q.id', 'asc')
            ->get();

        return response()->json([
            'questionReport' => $questionReport
        ]);
    }

    public function getSurveyList(Request $request){
        $teacherId = $request->teacher_id ?? null;
        $fromDate  = $request->from_date ?? null;
        $toDate    = $request->to_date ?? null;
        $user = auth()->user();

        $query = DB::table('teacher_evaluations as te')
            ->join('users as t', 'te.teacher_id', '=', 't.user_id')
            ->join('students as s', 'te.student_id', '=', 's.student_id')
            ->join('departments as d', 'd.id', '=', 't.department_id')
            ->join('designations as dd', 'dd.id', '=', 't.designation_id')
            ->select(
                'te.id as evaluation_id',
                't.BMDC_NO as BMDC_NO',
                't.name as teacher_name',
                'd.name as department_name',
                'dd.name as designation_name',
                'te.submitted_date',
                DB::raw('(SELECT SUM(ted.rating)
                  FROM teacher_evaluations_details as ted
                  JOIN evaluation_statements as es
                    ON ted.evaluation_statement_id = es.id
                  WHERE ted.teacher_evaluation_id = te.id
                    AND es.type = "rating"
                ) as total_score'),
                DB::raw('(SELECT SUM(es.rating)
                  FROM teacher_evaluations_details as ted
                  JOIN evaluation_statements as es
                    ON ted.evaluation_statement_id = es.id
                  WHERE ted.teacher_evaluation_id = te.id
                    AND es.type = "rating"
                ) as out_of'),
                DB::raw('(SELECT GROUP_CONCAT(
                    CASE 
                        WHEN ted.rating = 1 THEN "Yes"
                        ELSE "No"
                    END SEPARATOR ", "
                  )
                  FROM teacher_evaluations_details as ted
                  JOIN evaluation_statements as es
                    ON ted.evaluation_statement_id = es.id
                  WHERE ted.teacher_evaluation_id = te.id
                    AND es.type = "boolean"
                ) as role_model'),
                // Total students surveyed for this teacher
                DB::raw('(SELECT COUNT(DISTINCT te2.student_id)
                  FROM teacher_evaluations as te2
                  WHERE te2.teacher_id = te.teacher_id
                ) as total_students_surveyed'),
                DB::raw('(SELECT COUNT(*) FROM students) as total_students')
            );
        if ($teacherId) {
            $query->where('te.teacher_id', $teacherId);
        }
        if ($fromDate && $toDate) {
            $query->whereBetween('te.submitted_date', [$fromDate, $toDate]);
        }
        if ($user->role_id === 4) {
            $query->where('te.teacher_id', $user->user_id);
        } elseif ($user->is_head === 'Y') {
            $query->where('t.department_id', $user->department_id);
        }
        $surveyList = $query->orderBy('te.submitted_date', 'desc')->paginate(10);
        return response()->json([
            'surveyList' => $surveyList
        ]);
    }

    public function getTeacherDetails(Request $request){
        $evaluation_id = $request->evaluationId;

        $detailsQuery = DB::table('teacher_evaluations as te')
            ->join('users as t', 'te.teacher_id', '=', 't.user_id')
            ->join('students as s', 'te.student_id', '=', 's.student_id')
            ->join('teacher_evaluations_details as ted', 'ted.teacher_evaluation_id', '=', 'te.id')
            ->join('evaluation_statements as es', 'ted.evaluation_statement_id', '=', 'es.id')
            ->select(
                't.name as teacher_name',
                DB::raw("s.name as student_name"),
                'te.student_phase',
                'te.submitted_date',
                'es.statement as question',
                'es.type as question_type',
                'es.rating as total_rating',
                DB::raw('CASE 
                    WHEN es.type = "boolean" AND ted.rating = 1 THEN "Yes"
                    WHEN es.type = "boolean" AND ted.rating = 0 THEN "No"
                    ELSE ted.rating
                 END as answer')
            )
            ->where('ted.teacher_evaluation_id',$evaluation_id)
            ->orderBy('t.name')
            ->orderBy('s.student_id')
            ->orderBy('es.id')->get();

        //$details = TeacherEvaluation::query()->with('teacher','details','details.statement')->where('id',$evaluation_id)->first();
        return response()->json([
            'details' => $detailsQuery
        ]);
    }

    public function getSingleTeacherEvaluation(Request $request){
        $teacherId = $request->teacherId;

        $query = DB::table('teacher_evaluations as te')
            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
            ->join('evaluation_statements as es', 'ted.evaluation_statement_id', '=', 'es.id')
            ->where('te.teacher_id', $teacherId)
            ->select(
                'es.statement',
                'ted.evaluation_statement_id',
                'es.type as question_type',
                //DB::raw('ROUND(AVG(ted.rating), 2) as answer'),
                DB::raw("
                    CASE 
                        WHEN es.type = 'boolean' THEN 
                            CASE WHEN ROUND(AVG(ted.rating), 0) = 1 THEN 'Yes'
                                 ELSE 'No'
                            END
                        ELSE ROUND(AVG(ted.rating), 2)
                    END as answer
                "),
                DB::raw("
                    SUM(CASE WHEN es.type = 'boolean' AND ted.rating = 1 THEN 1 ELSE 0 END) as yes_count
                "),
                DB::raw("
                    SUM(CASE WHEN es.type = 'boolean' AND ted.rating = 0 THEN 1 ELSE 0 END) as no_count
                "),
                DB::raw('5 as total_rating'),
                //DB::raw('COUNT(te.student_id) as total_students') // কতজন student survey করেছে
            )
            ->groupBy('ted.evaluation_statement_id', 'es.statement', 'es.type')
            ->orderBy('es.ordering')
            ->get();

        return response()->json([
            'details' => $query
        ]);
    }

    public function getSingleTeacherEvaluationPrint($id){
        $teacherId = $id;

        $query = DB::table('teacher_evaluations as te')
            ->join('teacher_evaluations_details as ted', 'te.id', '=', 'ted.teacher_evaluation_id')
            ->join('evaluation_statements as es', 'ted.evaluation_statement_id', '=', 'es.id')
            ->where('te.teacher_id', $teacherId)
            ->select(
                'es.statement',
                'ted.evaluation_statement_id',
                'es.type as question_type',
                //DB::raw('ROUND(AVG(ted.rating), 2) as answer'),
                DB::raw("
                    CASE 
                        WHEN es.type = 'boolean' THEN 
                            CASE WHEN ROUND(AVG(ted.rating), 0) = 1 THEN 'Yes'
                                 ELSE 'No'
                            END
                        ELSE ROUND(AVG(ted.rating), 2)
                    END as answer
                "),
                DB::raw("
                    SUM(CASE WHEN es.type = 'boolean' AND ted.rating = 1 THEN 1 ELSE 0 END) as yes_count
                "),
                DB::raw("
                    SUM(CASE WHEN es.type = 'boolean' AND ted.rating = 0 THEN 1 ELSE 0 END) as no_count
                "),
                DB::raw('5 as total_rating'),
            )
            ->groupBy('ted.evaluation_statement_id', 'es.statement', 'es.type')
            ->orderBy('es.ordering')
            ->get();

        $teacher = User::query()
            ->with(['department','designation'])
            ->where('user_id',$teacherId)->first();

        return response()->json([
            'details' => $query,
            'teacher' => $teacher,
        ]);
    }

    protected function getUserRole()
    {
        return Auth::user()->role->name;
    }

    protected function getUserDepartmentId()
    {
        return Auth::user()->department_id;
    }

    protected function getUserId()
    {
        return Auth::user()->id;
    }

    protected function isAdmin()
    {
        return $this->getUserRole() === 'admin';
    }

    protected function isDepartmentHead()
    {
        return $this->getUserRole() === 'department_head';
    }

    protected function isTeacher()
    {
        return $this->getUserRole() === 'teacher';
    }

    protected function isStudent()
    {
        return $this->getUserRole() === 'student';
    }

    protected function jsonResponse($data, $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    protected function jsonError($message = 'Error', $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}

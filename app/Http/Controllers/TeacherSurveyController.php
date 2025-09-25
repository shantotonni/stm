<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeacherEvaluationDetailsCollection;
use App\Models\EvaluationStatement;
use App\Models\Student;
use App\Models\TeacherEvaluation;
use App\Models\TeacherEvaluationDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TeacherSurveyController extends Controller
{
    function __construct() {
        Config::set('jwt.user', Student::class);
        Config::set('auth.providers', ['users' => [
            'driver' => 'eloquent',
            'model' => Student::class,
        ]]);
    }

    public function surveySubmitted(Request $request){

        $request->validate([
            'answers' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $student = auth()->user();
            $date = date('Y-m-d');

            $survey_exists = TeacherEvaluation::query()->where('submitted_date',$date)
                ->where('student_id',$student->student_id)
                ->where('roll_no',$student->roll_no)
                ->where('teacher_id',$request->teacher_id)
                ->exists();
            if ($survey_exists){
                return response()->json([
                    'status' => 'error',
                    'message' => 'In one day, a student can take the survey for a teacher only once.'
                ]);
            }

            $teacher_evaluation = new TeacherEvaluation();
            $teacher_evaluation->student_name = $student->name;
            $teacher_evaluation->teacher_id = $request->teacher_id;
            $teacher_evaluation->student_phase = $request->selectedPhase;
            $teacher_evaluation->roll_no = $student->roll_no;
            $teacher_evaluation->student_id = $student->student_id;
            $teacher_evaluation->submitted_date = $date;
            $teacher_evaluation->save();

            foreach ($request->answers as $questionId => $answerValue) {
                $statement = EvaluationStatement::find($questionId);
                if ($statement->type === 'boolean') {
                    $answerValue = $answerValue === 'Y' ? 1 : 0;
                }
                TeacherEvaluationDetails::create([
                    'teacher_evaluation_id'  => $teacher_evaluation->id,
                    'evaluation_statement_id' => $questionId,
                    'rating' => $answerValue,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Survey created successfully'
            ]);

        }catch (\Exception $exception){
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 400);
        }

    }



}

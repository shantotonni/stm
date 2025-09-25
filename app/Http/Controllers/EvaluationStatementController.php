<?php

namespace App\Http\Controllers;

use App\Http\Resources\EvaluationStatementCollection;
use App\Models\EvaluationStatement;
use Illuminate\Http\Request;

class EvaluationStatementController extends Controller
{
    public function index(Request $request)
    {
        $statements = EvaluationStatement::query()->paginate(15);
        return new EvaluationStatementCollection($statements);
    }

    public function store(Request $request)
    {
        try {

            $statements = new EvaluationStatement();
            $statements->statement = $request->statement;
            $statements->ordering = $request->ordering;
            $statements->is_active = $request->is_active;
            $statements->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Statement created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'=>400,
                'message'=>$e->getMessage()
            ],400);
        }
    }

    public function update(Request $request,$id)
    {
        try {

            $statements = EvaluationStatement::find($id);
            $statements->statement = $request->statement;
            $statements->ordering = $request->ordering;
            $statements->is_active = $request->is_active;
            $statements->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Statement created successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'=>400,
                'message'=>$e->getMessage()
            ],400);
        }
    }

    public function destroy($id)
    {
        $statements = EvaluationStatement::where('id',$id)->first();
        $statements->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Statement Deleted successfully'
        ]);
    }

    public function search($query)
    {
        return new EvaluationStatementCollection(EvaluationStatement::where('statement','LIKE',"%$query%")->latest()->paginate(20));
    }
}

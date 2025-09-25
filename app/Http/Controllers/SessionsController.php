<?php

namespace App\Http\Controllers;

use App\Models\Sessions;
use Illuminate\Http\Request;

class SessionsController extends Controller
{
    public function index(Request $request)
    {
        $take = $request->take;
        return Sessions::select('session_id','name as Name','from_period as FromPeriod', 'to_period as ToPeriod','batch_number as BatchNumber','status as Status')->orderBy('from_period','asc')->paginate($take);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'fromPeriod' => 'required',
            'toPeriod' => 'required',
            'batch_number' => 'required',
            'status' => 'required'
        ]);
        try {
            Sessions::create([
                'name' => $request->name,
                'from_period' => $request->fromPeriod,
                'to_period' => $request->toPeriod,
                'batch_number' => $request->batch_number,
                'status' => $request->status
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Session has been created successfully'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ],500);
        }
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'name' => 'required',
            'fromPeriod' => 'required',
            'toPeriod' => 'required',
            'batch_number' => 'required',
            'status' => 'required'
        ]);
        try {
            Sessions::where('session_id',$id)->update([
                'name' => $request->name,
                'from_period' => $request->fromPeriod,
                'to_period' => $request->toPeriod,
                'batch_number' => $request->batch_number,
                'status' => $request->status
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Session has been updated successfully'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ],500);
        }
    }

    public function byId($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Sessions::where('session_id',$id)->first()
        ]);
    }
}

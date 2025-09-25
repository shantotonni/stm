<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Head;
use App\Models\Sessions;
use App\Models\Year;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'sessions' => Sessions::where('status','Y')->get(),
            'years' => Year::where('status','Y')->get(),
            'categories' => Category::all(),
        ]);
    }

    public function sessionHeadData()
    {
        return response()->json([
            'status' => 'success',
            'sessions' => Sessions::where('status','Y')->get(),
            'heads' => Head::orderBy('created_at','desc')->get()
        ]);
    }
}

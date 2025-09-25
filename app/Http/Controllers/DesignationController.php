<?php

namespace App\Http\Controllers;

use App\Http\Resources\DesignationCollection;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $designations = Designation::query()->paginate(15);
        return new DesignationCollection($designations);
    }

    public function store(Request $request)
    {
        try {
            $exist_designation = Designation::where('name',$request->name)->exists();
            if ($exist_designation){
                return response()->json([
                    'status'=>400,
                    'message'=>'Already Added'
                ],400);
            }

            $designation = new Designation();
            $designation->name = $request->name;
            $designation->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Designation created successfully'
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

            $designation = Designation::find($id);
            $designation->name = $request->name;
            $designation->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Designation Updated successfully'
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
        Designation::where('id',$id)->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Designation Deleted successfully'
        ]);
    }

    public function search($query)
    {
        return new DesignationCollection(Designation::where('name','LIKE',"%$query%")->latest()->paginate(20));
    }
}

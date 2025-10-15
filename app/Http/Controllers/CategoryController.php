<?php

namespace App\Http\Controllers;

use App\Http\Resources\Category\CategoryCollection;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('activeBooks')
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request,[
           'currency_id' => 'required',
           'name' => 'required'
        ]);

        $category = new Category();
        $category->currency_id = $request->currency_id;
        $category->name = $request->name;
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully'
        ]);
    }

    public function show($id)
    {
        $category = Category::with(['activeBooks' => function ($query) {
            $query->with(['department', 'subject'])
                ->latest()
                ->limit(12);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function update(Request $request,$id)
    {
        $this->validate($request,[
            'currency_id' => 'required',
           'name' => 'required'
        ]);

        $category = Category::find($id);
        $category->currency_id = $request->currency_id;
        $category->name = $request->name;
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Category Updated successfully'
        ]);
    }

    public function destroy($id)
    {
        Category::where('id',$id)->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Category Deleted successfully'
        ]);
    }

    public function search($query)
    {
        return new CategoryCollection(Category::where('name','LIKE',"%$query%")->latest()->paginate(20));
    }
}

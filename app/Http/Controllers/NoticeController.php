<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NoticeController extends Controller
{
    /**
     * Display a listing of notices with filtering
     */
    public function index(Request $request)
    {
        $query = Notice::with(['creator:id,name', 'department:id,name'])
            ->orderBy('publish_date', 'desc');

        // Filters
        if ($request->has('notice_type') && $request->notice_type != '') {
            $query->where('notice_type', $request->notice_type);
        }

        if ($request->has('target_audience') && $request->target_audience != '') {
            $query->where('target_audience', $request->target_audience);
        }

        if ($request->has('year') && $request->year != '') {
            $query->where('year', $request->year);
        }

        if ($request->has('is_active') && $request->is_active != '') {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('department_id') && $request->department_id != '') {
            $query->where('department_id', $request->department_id);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Active only filter
        if ($request->has('active_only') && $request->active_only == '1') {
            $query->active();
        }

        $notices = $query->paginate($request->per_page ?? 10);

        return response()->json($notices);
    }

    /**
     * Store a newly created notice
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notice_type' => 'required|in:general,academic,exam,event,urgent',
            'target_audience' => 'required|in:all,students,teachers,department',
            'department_id' => 'nullable|exists:departments,id',
            'year' => 'nullable|in:1st,2nd,3rd,4th,5th',
            'is_active' => 'boolean',
            'publish_date' => 'required|date',
            'expire_date' => 'nullable|date|after_or_equal:publish_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id(); // Set current user as creator

        $notice = Notice::create($data);
        $notice->load(['creator', 'department']);

        return response()->json([
            'message' => 'Notice created successfully',
            'notice' => $notice
        ], 201);
    }

    /**
     * Display the specified notice
     */
    public function show($id)
    {
        $notice = Notice::with(['creator:id,name,email', 'department:id,name'])
            ->findOrFail($id);

        return response()->json($notice);
    }

    /**
     * Update the specified notice
     */
    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'notice_type' => 'sometimes|required|in:general,academic,exam,event,urgent',
            'target_audience' => 'sometimes|required|in:all,students,teachers,department',
            'department_id' => 'nullable|exists:departments,id',
            'year' => 'nullable|in:1st,2nd,3rd,4th,5th',
            'is_active' => 'sometimes|boolean',
            'publish_date' => 'sometimes|required|date',
            'expire_date' => 'nullable|date|after_or_equal:publish_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $notice->update($validator->validated());
        $notice->load(['creator', 'department']);

        return response()->json([
            'message' => 'Notice updated successfully',
            'notice' => $notice
        ]);
    }

    /**
     * Remove the specified notice
     */
    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return response()->json([
            'message' => 'Notice deleted successfully'
        ]);
    }

    /**
     * Toggle notice active status
     */
    public function toggleStatus($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->is_active = !$notice->is_active;
        $notice->save();

        return response()->json([
            'message' => 'Notice status updated',
            'is_active' => $notice->is_active
        ]);
    }

    /**
     * Get statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Notice::count(),
            'active' => Notice::active()->count(),
            'by_type' => Notice::selectRaw('notice_type, COUNT(*) as count')
                ->groupBy('notice_type')
                ->pluck('count', 'notice_type'),
            'by_audience' => Notice::selectRaw('target_audience, COUNT(*) as count')
                ->groupBy('target_audience')
                ->pluck('count', 'target_audience'),
        ];

        return response()->json($stats);
    }
}

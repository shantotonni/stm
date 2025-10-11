<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NoticeReportController extends ReportController
{
    public function index(Request $request)
    {
        $query = Notice::with(['department', 'publisher']);

        // Role-based filtering
        if ($this->isAdmin()) {
            // Admin can see all notices
        } elseif ($this->isDepartmentHead() || $this->isTeacher() || $this->isStudent()) {
            // All non-admin users see notices from their department or general notices
            $query->where(function ($q) {
                $q->where('department_id', $this->getUserDepartmentId())
                    ->orWhereNull('department_id'); // General notices
            });
        } else {
            return $this->jsonError('Unauthorized access', 403);
        }

        // Only show active notices (not expired)
        $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', Carbon::now());
        });

        // Apply filters
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->has('from_date')) {
            $query->where('publish_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('publish_date', '<=', $request->to_date);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'publish_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 12);
        $notices = $query->paginate($perPage);

        // Transform data
        $notices->getCollection()->transform(function ($notice) {
            $content = $notice->content;
            $preview = strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;

            return [
                'id' => $notice->id,
                'title' => $notice->title,
                'content' => $notice->content,
                'preview' => $preview,
                'department' => $notice->department ? $notice->department->name : 'All Departments',
                'publish_date' => $notice->publish_date,
                'expiry_date' => $notice->expiry_date,
                'priority' => $notice->priority,
                'is_urgent' => $notice->priority === 'urgent',
                'days_remaining' => $notice->expiry_date ? Carbon::parse($notice->expiry_date)->diffInDays(Carbon::now()) : null,
                'publisher' => $notice->publisher ? $notice->publisher->name : 'Admin',
            ];
        });

        return $this->jsonResponse($notices);
    }

    public function show($id)
    {
        $notice = Notice::with(['department', 'publisher'])->find($id);

        if (!$notice) {
            return $this->jsonError('Notice not found', 404);
        }

        // Check access permission
        if (!$this->isAdmin()) {
            if ($notice->department_id && $notice->department_id != $this->getUserDepartmentId()) {
                return $this->jsonError('Unauthorized access', 403);
            }
        }

        return $this->jsonResponse([
            'id' => $notice->id,
            'title' => $notice->title,
            'content' => $notice->content,
            'department' => $notice->department ? $notice->department->name : 'All Departments',
            'publish_date' => $notice->publish_date,
            'expiry_date' => $notice->expiry_date,
            'priority' => $notice->priority,
            'publisher' => $notice->publisher ? $notice->publisher->name : 'Admin',
            'attachments' => $notice->attachments ?? [],
        ]);
    }
}

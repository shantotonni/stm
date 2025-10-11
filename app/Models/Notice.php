<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'content', 'notice_type', 'target_audience', 'department_id',
        'year', 'is_active', 'publish_date', 'expire_date', 'created_by'
    ];

    protected $casts = [
        'publish_date' => 'date',
        'expire_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('publish_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('expire_date')
                    ->orWhere('expire_date', '>=', now());
            });
    }

    public function getStatusBadgeAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        if ($this->is_expired) {
            return 'Expired';
        }
        if (Carbon::parse($this->publish_date)->isFuture()) {
            return 'Scheduled';
        }
        return 'Active';
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('notice_type', $type);
    }

    /**
     * Scope for filtering by audience
     */
    public function scopeForAudience($query, $audience)
    {
        return $query->where(function($q) use ($audience) {
            $q->where('target_audience', 'all')
                ->orWhere('target_audience', $audience);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'department_id',
        'subject_id',
        'title',
        'author',
        'publisher',
        'isbn',
        'edition',
        'publication_year',
        'pages',
        'description',
        'cover_image',
        'file_path',
        'file_type',
        'file_size',
        'download_count',
        'view_count',
        'rating',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'is_featured' => 'integer',
        'is_active' => 'integer',
        'download_count' => 'integer',
        'view_count' => 'integer',
        'rating' => 'decimal:2',
        'pages' => 'integer',
        'file_size' => 'integer',
    ];

    protected $appends = [
        'cover_image_url',
        'file_url',
        'formatted_file_size',
        'is_favorited',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function favorites()
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function downloads()
    {
        return $this->hasMany(BookDownload::class);
    }

    public function ratings()
    {
        return $this->hasMany(BookRating::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'user_favorites')
            ->withTimestamps();
    }

    // Accessors
    public function getCoverImageUrlAttribute()
    {
        if ($this->cover_image) {
            return Storage::url($this->cover_image);
        }
        return asset('images/default-book-cover.jpg');
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) return 'N/A';

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getIsFavoritedAttribute()
    {
        if (auth()->check()) {
            return $this->favorites()
                ->where('user_id', auth()->id())
                ->exists();
        }
        return false;
    }

    // Methods
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function updateRating()
    {
        $avgRating = $this->ratings()->avg('rating');
        $this->update(['rating' => $avgRating ?? 0]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%");
        });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('view_count', 'desc')
            ->orderBy('download_count', 'desc')
            ->limit($limit);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }
}

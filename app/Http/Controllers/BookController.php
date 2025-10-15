<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookDownload;
use App\Models\BookRating;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index(Request $request)
    {

        $query = Book::with(['category', 'department', 'subject'])
            ->active();

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->search($request->search);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id != '') {
            $query->byCategory($request->category_id);
        }

        // Filter by department
        if ($request->has('department_id') && $request->department_id != '') {
            $query->byDepartment($request->department_id);
        }

        // Filter by subject
        if ($request->has('subject_id') && $request->subject_id != '') {
            $query->bySubject($request->subject_id);
        }

        // Filter by language
        if ($request->has('language') && $request->language != '') {
            $query->where('language', $request->language);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'popular') {
            $query->orderBy('view_count', 'desc')
                ->orderBy('download_count', 'desc');
        } elseif ($sortBy === 'rating') {
            $query->orderBy('rating', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 12);
        $books = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    /**
     * Get featured books
     */
    public function featured()
    {
        $books = Book::with(['category', 'department', 'subject'])
            ->active()
            ->featured()
            ->latest()
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    /**
     * Get popular books
     */
    public function popular()
    {
        $books = Book::with(['category', 'department', 'subject'])
            ->active()
            ->popular(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    /**
     * Get recent books
     */
    public function recent()
    {
        $books = Book::with(['category', 'department', 'subject'])
            ->active()
            ->recent(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    /**
     * Display the specified book
     */
    public function show($id)
    {
        $book = Book::with(['category', 'department', 'subject', 'ratings.user'])
            ->findOrFail($id);

        // Increment view count
        $book->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => $book,
        ]);
    }

    /**
     * Store a newly created book
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'department_id' => 'nullable|exists:departments,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'edition' => 'nullable|string|max:50',
            'publication_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'pages' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'file' => 'required|mimes:pdf,epub,mobi|max:51200', // 50MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except(['cover_image', 'file']);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('books/covers', 'public');
            $data['cover_image'] = $coverPath;
        }

        // Handle book file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('books/files', 'public');

            $data['file_path'] = $filePath;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

       // return $data;
        $data['is_active']   = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $book = Book::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Book created successfully',
            'data' => $book->load(['category', 'department', 'subject']),
        ], 201);
    }

    /**
     * Update the specified book
     */
    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'department_id' => 'nullable|exists:departments,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'edition' => 'nullable|string|max:50',
            'publication_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'language' => 'nullable|string|max:50',
            'pages' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'file' => 'nullable|mimes:pdf,epub,mobi|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except(['cover_image', 'file']);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }

            $coverPath = $request->file('cover_image')->store('books/covers', 'public');
            $data['cover_image'] = $coverPath;
        }

        // Handle book file upload
        if ($request->hasFile('file')) {
            // Delete old file
            if ($book->file_path) {
                Storage::disk('public')->delete($book->file_path);
            }

            $file = $request->file('file');
            $filePath = $file->store('books/files', 'public');

            $data['file_path'] = $filePath;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $book->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Book updated successfully',
            'data' => $book->load(['category', 'department', 'subject']),
        ]);
    }

    /**
     * Remove the specified book
     */
    public function destroy($id)
    {
        $book = Book::findOrFail($id);

        // Delete files
        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        if ($book->file_path) {
            Storage::disk('public')->delete($book->file_path);
        }

        $book->delete();

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully',
        ]);
    }

    /**
     * Download book
     */
    public function download($id)
    {
        $book = Book::findOrFail($id);

        if (!$book->file_path || !Storage::disk('public')->exists($book->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        // Log download
        if (auth()->check()) {
            BookDownload::create([
                'user_id' => auth()->id(),
                'book_id' => $book->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        // Increment download count
        $book->incrementDownloadCount();

        return Storage::disk('public')->download(
            $book->file_path,
            $book->title . '.' . $book->file_type
        );
    }

    /**
     * Toggle favorite
     */
    public function toggleFavorite($id)
    {
        $book = Book::findOrFail($id);
        $user = auth()->user();

        $favorite = UserFavorite::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
            $message = 'Removed from favorites';
        } else {
            UserFavorite::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
            $isFavorited = true;
            $message = 'Added to favorites';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_favorited' => $isFavorited,
        ]);
    }

    /**
     * Get user favorites
     */
    public function favorites()
    {
        $user = auth()->user();

        $favorites = $user->favoriteBooks()
            ->with(['category', 'department', 'subject'])
            ->latest('user_favorites.created_at')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    /**
     * Rate a book
     */
    public function rate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $book = Book::findOrFail($id);
        $user = auth()->user();

        $bookRating = BookRating::updateOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $book->id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'data' => $bookRating,
        ]);
    }
}

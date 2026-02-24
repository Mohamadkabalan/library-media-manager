<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCheckout;
use App\Models\BookRating;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class BookController extends Controller
{   use AuthorizesRequests;
    public function __construct(private AiService $ai) {}

    public function index(Request $request)
    {
        $query = Book::with('addedBy')->active();

        // Search
        if ($search = $request->get('search')) {
            if ($request->get('ai_search')) {
                // AI-enhanced search
                $interpreted = $this->ai->interpretSearchQuery($search);
                $query = $this->applyAiSearch($query, $interpreted, $search);
            } else {
                $query->search($search);
            }
        }

        // Filters
        if ($genre = $request->get('genre')) {
            $query->byGenre($genre);
        }
        if ($request->get('available_only')) {
            $query->available();
        }

        // Sort
        $sort = $request->get('sort', 'title');
        match ($sort) {
            'newest' => $query->latest(),
            'popular' => $query->orderByDesc('times_borrowed'),
            'rating' => $query->orderByDesc('average_rating'),
            'author' => $query->orderBy('author'),
            default => $query->orderBy('title'),
        };

        $books = $query->paginate(12)->withQueryString();
        $genres = Book::active()->distinct()->pluck('genre')->filter()->sort()->values();

        return view('books.index', compact('books', 'genres', 'sort'));
    }

    public function create()
    {
        $this->authorize('create', Book::class);
        return view('books.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books',
            'genre' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'total_pages' => 'nullable|integer|min:1',
            'language' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:100',
            'total_copies' => 'required|integer|min:1|max:100',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $data['available_copies'] = $data['total_copies'];
        $data['added_by'] = Auth::id();

        $book = Book::create($data);

        // Queue AI enrichment
        if ($request->boolean('ai_enrich')) {
            $metadata = $this->ai->enrichBookMetadata($book->title, $book->author);
            if (!empty($metadata)) {
                $book->update(array_filter([
                    'genre' => $book->genre ?? ($metadata['genre'] ?? null),
                    'description' => $book->description ?? ($metadata['description'] ?? null),
                    'publication_year' => $book->publication_year ?? ($metadata['publication_year'] ?? null),
                    'total_pages' => $book->total_pages ?? ($metadata['total_pages'] ?? null),
                    'publisher' => $book->publisher ?? ($metadata['publisher'] ?? null),
                    'ai_tags' => $metadata['tags'] ?? null,
                ]));
            }
        }

        return redirect()->route('books.show', $book)->with('success', 'Book added successfully!');
    }

    public function show(Book $book)
    {
        $book->load(['ratings.user', 'activeCheckouts.user']);
        $userCheckout = null;
        $userRating = null;

        if (Auth::check()) {
            $userCheckout = $book->checkouts()
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->first();
            $userRating = $book->ratings()->where('user_id', Auth::id())->first();
        }

        // Lazy generate AI summary if missing
        if (!$book->ai_summary) {
            try {
                $summary = $this->ai->generateBookSummary($book);
                $book->update(['ai_summary' => $summary]);
            } catch (\Exception $e) {
                // Non-blocking
            }
        }

        $borrowHistory = $book->checkouts()
            ->with('user')
            ->whereNotNull('returned_at')
            ->latest('returned_at')
            ->limit(5)
            ->get();

        return view('books.show', compact('book', 'userCheckout', 'userRating', 'borrowHistory'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn,' . $book->id,
            'genre' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'total_pages' => 'nullable|integer|min:1',
            'language' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:100',
            'total_copies' => 'required|integer|min:' . ($book->total_copies - $book->available_copies),
            'cover_image' => 'nullable|image|max:2048',
            'status' => 'in:active,archived,lost',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        // Adjust available copies proportionally
        $copiesDiff = $data['total_copies'] - $book->total_copies;
        $data['available_copies'] = max(0, $book->available_copies + $copiesDiff);

        // Clear AI cache if content changed
        if ($data['title'] !== $book->title || $data['description'] !== $book->description) {
            Cache::forget("ai_book_summary_{$book->id}");
            Cache::forget("ai_book_tags_{$book->id}");
            $data['ai_summary'] = null;
        }

        $book->update($data);

        return redirect()->route('books.show', $book)->with('success', 'Book updated successfully!');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        if ($book->available_copies < $book->total_copies) {
            return back()->with('error', 'Cannot delete a book with active checkouts.');
        }

        $book->delete();
        return redirect()->route('books.index')->with('success', 'Book deleted.');
    }

    // ─── Checkout / Check-in ─────────────────────────────────────────────────

    public function checkout(Request $request, Book $book)
    {
        $this->authorize('checkout', $book);

        if (!$book->is_available) {
            return back()->with('error', 'This book is not available for checkout.');
        }

        $existingCheckout = $book->checkouts()
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        if ($existingCheckout) {
            return back()->with('error', 'You already have this book checked out.');
        }

        $book->checkOut(Auth::user(), 14, Auth::user());

        return back()->with('success', "'{$book->title}' checked out! Due in 14 days.");
    }

    public function checkin(Request $request, Book $book)
    {
        $this->authorize('checkin', $book);

        $checkout = $book->checkouts()
            ->where('user_id', $request->get('user_id', Auth::id()))
            ->where('status', 'active')
            ->firstOrFail();

        $book->checkIn($checkout, Auth::user());

        return back()->with('success', "'{$book->title}' returned successfully.");
    }

    public function renewCheckout(Book $book)
    {
        $checkout = $book->checkouts()
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->firstOrFail();

        try {
            $checkout->renew(14);
            return back()->with('success', 'Checkout renewed for 14 more days.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ─── Ratings ─────────────────────────────────────────────────────────────

    public function rate(Request $request, Book $book)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        BookRating::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => Auth::id()],
            $data
        );

        $book->updateAverageRating();

        return back()->with('success', 'Rating submitted!');
    }

    // ─── AI Actions ───────────────────────────────────────────────────────────

    public function generateSummary(Book $book)
    {
        $this->authorize('update', $book);
        Cache::forget("ai_book_summary_{$book->id}");

        $summary = $this->ai->generateBookSummary($book);
        $book->update(['ai_summary' => $summary]);

        return back()->with('success', 'AI summary generated!');
    }

    public function generateTags(Book $book)
    {
        $this->authorize('update', $book);
        Cache::forget("ai_book_tags_{$book->id}");

        $tags = $this->ai->generateBookTags($book);
        $book->update(['ai_tags' => $tags]);

        return back()->with('success', 'AI tags generated!');
    }

    public function aiEnrich(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
        ]);

        $metadata = $this->ai->enrichBookMetadata($request->title, $request->author);
        return response()->json($metadata);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function applyAiSearch($query, array $interpreted, string $original)
    {
        if (!empty($interpreted['author'])) {
            $query->where('author', 'LIKE', '%' . $interpreted['author'] . '%');
        }
        if (!empty($interpreted['genre'])) {
            $query->where('genre', 'LIKE', '%' . $interpreted['genre'] . '%');
        }
        if (!empty($interpreted['keywords'])) {
            $query->where(function ($q) use ($interpreted) {
                foreach ($interpreted['keywords'] as $kw) {
                    $q->orWhere('title', 'LIKE', "%{$kw}%")
                      ->orWhere('description', 'LIKE', "%{$kw}%")
                      ->orWhere('ai_summary', 'LIKE', "%{$kw}%");
                }
            });
        }

        // If nothing matched, fall back to regular search
        if (empty($interpreted['author']) && empty($interpreted['genre']) && empty($interpreted['keywords'])) {
            $query->search($original);
        }

        return $query;
    }
}

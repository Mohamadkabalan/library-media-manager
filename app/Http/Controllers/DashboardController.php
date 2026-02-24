<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCheckout;
use App\Models\MediaItem;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function index()
    {
        $user = Auth::user();
        $isLibrarian = $user->is_librarian;

        // Library stats
        $myCheckouts = $user->activeCheckouts()->with('book')->get();
        $overdueCheckouts = $myCheckouts->filter->is_overdue;

        // Media stats
        $myMedia = MediaItem::forUser($user->id);
        $mediaStats = [
            'total' => $myMedia->count(),
            'currently_using' => (clone $myMedia)->byStatus('currently_using')->count(),
            'wishlist' => (clone $myMedia)->byStatus('wishlist')->count(),
            'completed' => (clone $myMedia)->byStatus('completed')->count(),
        ];

        // AI Recommendations
        $bookRecommendations = [];
        $mediaRecommendations = [];
        try {
            $bookRecommendations = $this->ai->getBookRecommendations($user, 4);
            $mediaRecommendations = $this->ai->getMediaRecommendations($user, 'movie', 4);
        } catch (\Exception $e) {}

        // Librarian extras
        $libraryStats = null;
        $recentActivity = null;
        if ($isLibrarian) {
            $libraryStats = [
                'total_books' => Book::active()->count(),
                'available' => Book::available()->count(),
                'checked_out' => BookCheckout::where('status', 'active')->count(),
                'overdue' => BookCheckout::where('status', 'active')->where('due_date', '<', now())->count(),
                'popular_genres' => Book::active()->whereNotNull('genre')
                    ->selectRaw('genre, COUNT(*) as count')
                    ->groupBy('genre')->orderByDesc('count')->limit(5)->get(),
            ];
            $recentActivity = BookCheckout::with(['book', 'user'])
                ->latest()->limit(8)->get();
        }

        // Recently added books
        $recentBooks = Book::active()->latest()->limit(6)->get();
        $recentMedia = MediaItem::forUser($user->id)->latest()->limit(4)->get();

        return view('dashboard', compact(
            'myCheckouts', 'overdueCheckouts', 'mediaStats',
            'bookRecommendations', 'mediaRecommendations',
            'libraryStats', 'recentActivity', 'recentBooks', 'recentMedia'
        ));
    }
}

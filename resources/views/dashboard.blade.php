@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Greeting --}}
    <div class="gradient-brand rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                    {{ explode(' ', auth()->user()->name)[0] }}! 👋
                </h2>
                <p class="text-blue-200 mt-1">Here's what's happening in your library & media world.</p>
            </div>
            <img src="{{ auth()->user()->avatar_url }}" class="w-16 h-16 rounded-2xl ring-4 ring-white/30 hidden sm:block">
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">My Checkouts</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $myCheckouts->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
                    <i class="fas fa-book text-blue-600 dark:text-blue-400 text-lg"></i>
                </div>
            </div>
            @if($overdueCheckouts->count() > 0)
            <p class="text-xs text-red-500 mt-2 font-medium">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ $overdueCheckouts->count() }} overdue!
            </p>
            @endif
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">My Media</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $mediaStats['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-600 dark:text-purple-400 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ $mediaStats['currently_using'] }} in progress</p>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $mediaStats['completed'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/40 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Media items finished</p>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Wishlist</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $mediaStats['wishlist'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/40 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-yellow-500 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Items to explore</p>
        </div>
    </div>

    {{-- Library Admin Stats --}}
    @if($libraryStats)
    <div class="card p-6">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-4">
            <i class="fas fa-chart-pie text-blue-600 mr-2"></i>Library Overview
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <p class="text-2xl font-bold text-blue-600">{{ $libraryStats['total_books'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Total Books</p>
            </div>
            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                <p class="text-2xl font-bold text-green-600">{{ $libraryStats['available'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Available</p>
            </div>
            <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                <p class="text-2xl font-bold text-orange-600">{{ $libraryStats['checked_out'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Checked Out</p>
            </div>
            <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                <p class="text-2xl font-bold text-red-600">{{ $libraryStats['overdue'] }}</p>
                <p class="text-xs text-gray-500 mt-1">Overdue</p>
            </div>
        </div>
        @if($libraryStats['overdue'] > 0)
        <a href="{{ route('admin.overdue') }}" class="text-sm text-red-600 hover:underline font-medium">
            <i class="fas fa-exclamation-triangle mr-1"></i>View overdue report →
        </a>
        @endif
    </div>
    @endif

    {{-- My Active Checkouts --}}
    @if($myCheckouts->count() > 0)
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                <i class="fas fa-book-reader text-blue-600 mr-2"></i>Currently Borrowed
            </h3>
            <a href="{{ route('books.index') }}" class="text-sm text-blue-600 hover:underline">Browse library →</a>
        </div>
        <div class="space-y-3">
            @foreach($myCheckouts as $checkout)
            <div class="flex items-center gap-4 p-3 {{ $checkout->is_overdue ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-gray-700/50' }} rounded-xl">
                <img src="{{ $checkout->book->cover_url }}" class="w-12 h-16 object-cover rounded-lg shadow-sm" alt="">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 dark:text-white text-sm truncate">{{ $checkout->book->title }}</p>
                    <p class="text-xs text-gray-500">{{ $checkout->book->author }}</p>
                    <p class="text-xs mt-1 {{ $checkout->is_overdue ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                        @if($checkout->is_overdue)
                            <i class="fas fa-exclamation-triangle mr-1"></i>Overdue since {{ $checkout->due_date->format('M d') }}
                        @else
                            Due {{ $checkout->due_date->format('M d, Y') }} ({{ $checkout->days_remaining }} days left)
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    @if($checkout->renewal_count < 3 && !$checkout->is_overdue)
                    <form method="POST" action="{{ route('books.renew', $checkout->book) }}">
                        @csrf
                        <button class="text-xs px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">Renew</button>
                    </form>
                    @endif
                    <a href="{{ route('books.show', $checkout->book) }}" class="text-xs px-3 py-1.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 transition">View</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- AI Book Recommendations --}}
    @if(!empty($bookRecommendations))
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                <i class="fas fa-robot text-purple-600 mr-2"></i>AI Book Recommendations
            </h3>
            <span class="badge bg-purple-100 text-purple-700">Powered by GPT</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($bookRecommendations as $rec)
            <div class="group relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-300 transition-all hover:shadow-md">
                @if(!empty($rec['book_id']))
                    <a href="{{ route('books.show', $rec['book_id']) }}" class="block">
                @endif
                <div class="aspect-[3/4] bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900 flex items-center justify-center p-4">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/50 rounded-xl flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-book text-blue-600 text-xl"></i>
                        </div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 line-clamp-2">{{ $rec['title'] }}</p>
                        <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $rec['author'] }}</p>
                    </div>
                </div>
                <div class="p-3">
                    @if(!empty($rec['genre']))
                        <span class="badge bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs">{{ $rec['genre'] }}</span>
                    @endif
                    @if(!empty($rec['in_library']))
                        <span class="badge bg-green-100 text-green-700 text-xs ml-1">In Library</span>
                    @endif
                    <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $rec['reason'] }}</p>
                </div>
                @if(!empty($rec['book_id']))
                    </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- AI Media Recommendations --}}
    @if(!empty($mediaRecommendations))
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                <i class="fas fa-film text-pink-600 mr-2"></i>AI Movie Picks For You
            </h3>
            <a href="{{ route('ai.chat') }}" class="text-sm text-purple-600 hover:underline">Chat with AI →</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($mediaRecommendations as $rec)
            <div class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-all">
                <div class="aspect-[3/4] bg-gradient-to-br from-pink-100 to-red-100 dark:from-pink-900 dark:to-red-900 flex items-center justify-center p-4">
                    <div class="text-center">
                        <div class="text-3xl mb-2">🎬</div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 line-clamp-2">{{ $rec['title'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $rec['year'] ?? '' }}</p>
                    </div>
                </div>
                <div class="p-3">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">{{ $rec['creator'] }}</p>
                    @if(!empty($rec['genre']))<span class="badge bg-pink-100 text-pink-700 text-xs mt-1">{{ $rec['genre'] }}</span>@endif
                    <p class="text-xs text-gray-400 mt-2 line-clamp-2">{{ $rec['reason'] }}</p>
                    <form method="POST" action="{{ route('media.store') }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="title" value="{{ $rec['title'] }}">
                        <input type="hidden" name="creator" value="{{ $rec['creator'] }}">
                        <input type="hidden" name="type" value="movie">
                        <input type="hidden" name="status" value="wishlist">
                        <input type="hidden" name="release_year" value="{{ $rec['year'] ?? '' }}">
                        <input type="hidden" name="genre" value="{{ $rec['genre'] ?? '' }}">
                        <button type="submit" class="w-full text-xs py-1.5 bg-pink-600 hover:bg-pink-700 text-white rounded-lg transition">
                            + Add to Wishlist
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recently Added Books --}}
    <div class="grid md:grid-cols-2 gap-6">
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                    <i class="fas fa-clock text-green-600 mr-2"></i>Recently Added Books
                </h3>
                <a href="{{ route('books.index') }}" class="text-sm text-blue-600 hover:underline">All books →</a>
            </div>
            <div class="space-y-3">
                @forelse($recentBooks as $book)
                <a href="{{ route('books.show', $book) }}" class="flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-xl transition">
                    <img src="{{ $book->cover_url }}" class="w-10 h-14 object-cover rounded-lg shadow-sm" alt="">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $book->title }}</p>
                        <p class="text-xs text-gray-500">{{ $book->author }}</p>
                        <span class="badge {{ $book->is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} text-xs mt-0.5">
                            {{ $book->is_available ? 'Available' : 'Checked Out' }}
                        </span>
                    </div>
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">No books yet. <a href="{{ route('books.create') }}" class="text-blue-600 hover:underline">Add the first one!</a></p>
                @endforelse
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                    <i class="fas fa-layer-group text-purple-600 mr-2"></i>Recent Media
                </h3>
                <a href="{{ route('media.index') }}" class="text-sm text-blue-600 hover:underline">All media →</a>
            </div>
            <div class="space-y-3">
                @forelse($recentMedia as $item)
                <a href="{{ route('media.show', $item) }}" class="flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-xl transition">
                    <div class="w-10 h-14 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 rounded-lg flex items-center justify-center text-xl shadow-sm">
                        {{ $item->type_icon }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-white truncate">{{ $item->title }}</p>
                        <p class="text-xs text-gray-500">{{ $item->creator }}</p>
                        <span class="badge bg-{{ $item->status_color }}-100 text-{{ $item->status_color }}-700 text-xs mt-0.5">
                            {{ $item->status_label }}
                        </span>
                    </div>
                    @if($item->rating)
                    <div class="flex items-center gap-1">
                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                        <span class="text-xs text-gray-600">{{ $item->rating }}</span>
                    </div>
                    @endif
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">No media yet. <a href="{{ route('media.create') }}" class="text-blue-600 hover:underline">Add some!</a></p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

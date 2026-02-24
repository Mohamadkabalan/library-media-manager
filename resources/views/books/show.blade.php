@extends('layouts.app')
@section('title', $book->title)
@section('page-title', 'Book Details')

@section('content')
<div class="space-y-6">

    {{-- Back --}}
    <a href="{{ route('books.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 transition">
        <i class="fas fa-arrow-left"></i> Back to catalog
    </a>

    {{-- Main Book Card --}}
    <div class="card overflow-hidden">
        <div class="md:flex">
            {{-- Cover --}}
            <div class="md:w-64 flex-shrink-0">
                <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
                     class="w-full h-80 md:h-full object-cover"
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22280%22><rect fill=%22%23e2e8f0%22 width=%22200%22 height=%22280%22/><text x=%22100%22 y=%22140%22 text-anchor=%22middle%22 fill=%22%2364748b%22 font-size=%2214%22>📚</text></svg>'">
            </div>

            {{-- Details --}}
            <div class="flex-1 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $book->title }}</h1>
                        <p class="text-gray-500 text-lg mt-1">by {{ $book->author }}</p>
                        @if($book->genre)
                        <span class="badge bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 mt-2">{{ $book->genre }}</span>
                        @endif
                    </div>
                    @can('update', $book)
                    <div class="flex gap-2 flex-shrink-0">
                        <a href="{{ route('books.edit', $book) }}" class="btn-secondary text-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Delete this book?')">
                            @csrf @method('DELETE')
                            <button class="btn-danger text-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    @endcan
                </div>

                {{-- Meta Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                    @if($book->isbn)<div class="text-sm"><p class="text-gray-400 text-xs">ISBN</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $book->isbn }}</p></div>@endif
                    @if($book->publication_year)<div class="text-sm"><p class="text-gray-400 text-xs">Year</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $book->publication_year }}</p></div>@endif
                    @if($book->publisher)<div class="text-sm"><p class="text-gray-400 text-xs">Publisher</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $book->publisher }}</p></div>@endif
                    @if($book->total_pages)<div class="text-sm"><p class="text-gray-400 text-xs">Pages</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $book->total_pages }}</p></div>@endif
                    @if($book->language)<div class="text-sm"><p class="text-gray-400 text-xs">Language</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $book->language }}</p></div>@endif
                    @if($book->location)<div class="text-sm"><p class="text-gray-400 text-xs">Location</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $book->location }}</p></div>@endif
                </div>

                {{-- Availability --}}
                <div class="flex items-center gap-4 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="text-center">
                        <p class="text-2xl font-bold {{ $book->is_available ? 'text-green-600' : 'text-red-500' }}">{{ $book->available_copies }}</p>
                        <p class="text-xs text-gray-400">Available</p>
                    </div>
                    <div class="h-10 w-px bg-gray-200 dark:bg-gray-600"></div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $book->total_copies }}</p>
                        <p class="text-xs text-gray-400">Total Copies</p>
                    </div>
                    <div class="h-10 w-px bg-gray-200 dark:bg-gray-600"></div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $book->times_borrowed }}</p>
                        <p class="text-xs text-gray-400">Times Borrowed</p>
                    </div>
                    @if($book->average_rating > 0)
                    <div class="h-10 w-px bg-gray-200 dark:bg-gray-600"></div>
                    <div class="text-center">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($book->average_rating, 1) }}</p>
                        </div>
                        <p class="text-xs text-gray-400">{{ $book->ratings->count() }} reviews</p>
                    </div>
                    @endif
                </div>

                {{-- Checkout / Checkin Actions --}}
                @auth
                @if($userCheckout)
                <div class="flex gap-3 flex-wrap">
                    <form method="POST" action="{{ route('books.checkin', $book) }}">
                        @csrf
                        <button class="btn-primary bg-green-600 hover:bg-green-700">
                            <i class="fas fa-undo"></i> Return Book
                        </button>
                    </form>
                    @if($userCheckout->renewal_count < 3)
                    <form method="POST" action="{{ route('books.renew', $book) }}">
                        @csrf
                        <button class="btn-secondary">
                            <i class="fas fa-calendar-plus"></i> Renew ({{ 3 - $userCheckout->renewal_count }} left)
                        </button>
                    </form>
                    @endif
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-clock mr-2"></i>
                        Due: {{ $userCheckout->due_date->format('M d, Y') }}
                        @if($userCheckout->is_overdue)
                        <span class="badge bg-red-100 text-red-700 ml-2">OVERDUE</span>
                        @endif
                    </div>
                </div>
                @elseif($book->is_available)
                <form method="POST" action="{{ route('books.checkout', $book) }}">
                    @csrf
                    <button class="btn-primary">
                        <i class="fas fa-hand-holding-heart"></i> Borrow This Book
                    </button>
                </form>
                @else
                <p class="text-sm text-red-500 font-medium">
                    <i class="fas fa-times-circle mr-1"></i>All copies are currently checked out.
                </p>
                @endif
                @endauth
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        {{-- AI Summary --}}
        <div class="md:col-span-2 space-y-4">
            @if($book->ai_summary)
            <div class="card p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-robot text-purple-500"></i> AI Summary
                    </h3>
                    @can('update', $book)
                    <form method="POST" action="{{ route('books.ai-summary', $book) }}">
                        @csrf
                        <button class="text-xs text-gray-400 hover:text-purple-600 transition">
                            <i class="fas fa-sync-alt mr-1"></i>Regenerate
                        </button>
                    </form>
                    @endcan
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $book->ai_summary }}</p>
            </div>
            @endif

            @if($book->description)
            <div class="card p-6">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-3">Description</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $book->description }}</p>
            </div>
            @endif

            {{-- AI Tags --}}
            @if(!empty($book->ai_tags_list))
            <div class="card p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-tags text-blue-500"></i> Topics & Tags
                    </h3>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($book->ai_tags_list as $tag)
                    <span class="badge bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-3 py-1 rounded-full">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- AI Generate Actions (Librarian) --}}
            @can('update', $book)
            <div class="card p-4 border-dashed border-2 border-purple-200 dark:border-purple-800">
                <p class="text-sm text-gray-500 mb-3 flex items-center gap-2">
                    <i class="fas fa-robot text-purple-500"></i>
                    <span>AI Librarian Tools</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    @if(!$book->ai_summary)
                    <form method="POST" action="{{ route('books.ai-summary', $book) }}">
                        @csrf
                        <button class="text-xs px-3 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 transition">
                            <i class="fas fa-magic mr-1"></i>Generate AI Summary
                        </button>
                    </form>
                    @endif
                    @if(empty($book->ai_tags_list))
                    <form method="POST" action="{{ route('books.ai-tags', $book) }}">
                        @csrf
                        <button class="text-xs px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 transition">
                            <i class="fas fa-tags mr-1"></i>Generate AI Tags
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endcan

            {{-- Ratings / Reviews --}}
            <div class="card p-6">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-4">Reader Reviews</h3>

                @auth
                <form method="POST" action="{{ route('books.rate', $book) }}" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl" x-data="{ rating: {{ $userRating?->rating ?? 0 }} }">
                    @csrf
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Your rating:</p>
                    <div class="flex gap-1 mb-3">
                        @for($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}"
                                class="text-2xl transition-transform hover:scale-110 focus:outline-none"
                                :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200 dark:text-gray-600'">★</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" :value="rating">
                    <textarea name="review" rows="2" placeholder="Share your thoughts (optional)..."
                              class="form-input text-sm mb-3">{{ $userRating?->review }}</textarea>
                    <button type="submit" :disabled="rating === 0" class="btn-primary text-sm" :class="{ 'opacity-50 cursor-not-allowed': rating === 0 }">
                        <i class="fas fa-star"></i> {{ $userRating ? 'Update Review' : 'Submit Review' }}
                    </button>
                </form>
                @endauth

                <div class="space-y-3">
                    @forelse($book->ratings()->with('user')->latest()->get() as $rating)
                    <div class="flex gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <img src="{{ $rating->user->avatar_url }}" class="w-9 h-9 rounded-full flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $rating->user->name }}</p>
                                <div class="flex">@for($i=1;$i<=5;$i++)<span class="text-xs {{ $rating->rating >= $i ? 'text-yellow-400' : 'text-gray-300' }}">★</span>@endfor</div>
                            </div>
                            @if($rating->review)
                            <p class="text-xs text-gray-500 mt-1">{{ $rating->review }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-4">No reviews yet. Be the first!</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar: Checkout History --}}
        <div class="space-y-4">
            @if(auth()->user()->is_librarian && $book->activeCheckouts->count() > 0)
            <div class="card p-4">
                <h3 class="font-semibold text-gray-800 dark:text-white text-sm mb-3">Currently Checked Out</h3>
                @foreach($book->activeCheckouts as $co)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ $co->user->avatar_url }}" class="w-8 h-8 rounded-full">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-200 truncate">{{ $co->user->name }}</p>
                        <p class="text-xs text-gray-400">Due {{ $co->due_date->format('M d') }}</p>
                    </div>
                    <form method="POST" action="{{ route('books.checkin', $book) }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $co->user_id }}">
                        <button class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200">Check In</button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            @if($borrowHistory->count() > 0)
            <div class="card p-4">
                <h3 class="font-semibold text-gray-800 dark:text-white text-sm mb-3">Recent Borrowers</h3>
                <div class="space-y-2">
                    @foreach($borrowHistory as $h)
                    <div class="flex items-center gap-2">
                        <img src="{{ $h->user->avatar_url }}" class="w-7 h-7 rounded-full">
                        <div>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ $h->user->name }}</p>
                            <p class="text-xs text-gray-400">Returned {{ $h->returned_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Books')
@section('page-title', 'Library Books')

@section('content')
<div class="space-y-6" x-data="booksPage()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Book Catalog</h2>
            <p class="text-sm text-gray-500 mt-0.5">Browse and search our library collection</p>
        </div>
        @can('create', App\Models\Book::class)
        <a href="{{ route('books.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> Add Book
        </a>
        @endcan
    </div>

    {{-- Search & Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('books.index') }}" class="flex flex-wrap gap-3">
            {{-- Search Input --}}
            <div class="flex-1 min-w-48">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by title, author, ISBN, genre..."
                           class="form-input pl-9">
                </div>
            </div>

            {{-- AI Search Toggle --}}
            <label class="flex items-center gap-2 px-3 py-2 bg-purple-50 dark:bg-purple-900/20 rounded-xl cursor-pointer border border-purple-100 dark:border-purple-800">
                <input type="checkbox" name="ai_search" value="1" {{ request('ai_search') ? 'checked' : '' }} class="rounded text-purple-600">
                <span class="text-sm text-purple-700 dark:text-purple-300 font-medium">
                    <i class="fas fa-robot mr-1"></i>AI Search
                </span>
            </label>

            {{-- Genre Filter --}}
            <select name="genre" class="form-input w-auto">
                <option value="">All Genres</option>
                @foreach($genres as $genre)
                <option value="{{ $genre }}" {{ request('genre') == $genre ? 'selected' : '' }}>{{ $genre }}</option>
                @endforeach
            </select>

            {{-- Availability --}}
            <label class="flex items-center gap-2 px-3 py-2 bg-green-50 dark:bg-green-900/20 rounded-xl cursor-pointer border border-green-100 dark:border-green-800">
                <input type="checkbox" name="available_only" value="1" {{ request('available_only') ? 'checked' : '' }} class="rounded text-green-600">
                <span class="text-sm text-green-700 dark:text-green-300 font-medium">Available only</span>
            </label>

            {{-- Sort --}}
            <select name="sort" class="form-input w-auto" onchange="this.form.submit()">
                <option value="title" {{ $sort == 'title' ? 'selected' : '' }}>Sort: Title</option>
                <option value="author" {{ $sort == 'author' ? 'selected' : '' }}>Sort: Author</option>
                <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>Sort: Newest</option>
                <option value="popular" {{ $sort == 'popular' ? 'selected' : '' }}>Sort: Most Popular</option>
                <option value="rating" {{ $sort == 'rating' ? 'selected' : '' }}>Sort: Top Rated</option>
            </select>

            <button type="submit" class="btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request()->anyFilled(['search', 'genre', 'available_only']))
            <a href="{{ route('books.index') }}" class="btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </form>

        {{-- AI Search Info --}}
        @if(request('ai_search') && request('search'))
        <div class="mt-3 flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400">
            <i class="fas fa-robot"></i>
            <span>AI-enhanced search active: interpreting "<strong>{{ request('search') }}</strong>" semantically</span>
        </div>
        @endif
    </div>

    {{-- Results --}}
    <div>
        <p class="text-sm text-gray-500 mb-4">
            Showing {{ $books->firstItem() }}-{{ $books->lastItem() }} of {{ $books->total() }} books
        </p>

        @if($books->isEmpty())
        <div class="card p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300">No books found</h3>
            <p class="text-gray-400 mt-2">Try different search terms or filters</p>
            @can('create', App\Models\Book::class)
            <a href="{{ route('books.create') }}" class="btn-primary mt-4 inline-flex">
                <i class="fas fa-plus"></i> Add the first book
            </a>
            @endcan
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($books as $book)
            <a href="{{ route('books.show', $book) }}"
               class="card overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 group block">
                {{-- Cover --}}
                <div class="aspect-[3/4] relative overflow-hidden">
                    <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22280%22><rect fill=%22%23e2e8f0%22 width=%22200%22 height=%22280%22/><text x=%22100%22 y=%22140%22 text-anchor=%22middle%22 fill=%22%2364748b%22 font-size=%2214%22>No Cover</text></svg>'">
                    {{-- Availability badge --}}
                    <div class="absolute top-2 right-2">
                        @if($book->is_available)
                        <span class="badge bg-green-500 text-white shadow-sm text-xs">{{ $book->available_copies }} avail.</span>
                        @else
                        <span class="badge bg-red-500 text-white shadow-sm text-xs">Checked out</span>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="p-3">
                    <p class="font-semibold text-gray-800 dark:text-white text-xs line-clamp-2 leading-tight">{{ $book->title }}</p>
                    <p class="text-xs text-gray-500 mt-1 truncate">{{ $book->author }}</p>
                    @if($book->genre)
                    <span class="badge bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs mt-1.5">{{ $book->genre }}</span>
                    @endif
                    @if($book->average_rating > 0)
                    <div class="flex items-center gap-1 mt-1.5">
                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                        <span class="text-xs text-gray-500">{{ number_format($book->average_rating, 1) }}</span>
                    </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $books->withQueryString()->links() }}</div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function booksPage() {
    return {}
}
</script>
@endpush

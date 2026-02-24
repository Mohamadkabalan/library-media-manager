@extends('layouts.app')
@section('title', 'My Media Collection')
@section('page-title', 'My Media Collection')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">My Collection</h2>
            <p class="text-sm text-gray-500 mt-0.5">Track movies, music, games, and more</p>
        </div>
        <a href="{{ route('media.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> Add Media
        </a>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total Items', 'value' => $stats['total'], 'icon' => 'layer-group', 'color' => 'blue'],
            ['label' => 'In Progress', 'value' => $stats['currently_using'], 'icon' => 'play-circle', 'color' => 'green'],
            ['label' => 'Completed', 'value' => $stats['completed'], 'icon' => 'check-circle', 'color' => 'purple'],
            ['label' => 'Wishlist', 'value' => $stats['wishlist'], 'icon' => 'star', 'color' => 'yellow'],
        ] as $stat)
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $stat['value'] }}</p>
                </div>
                <div class="w-10 h-10 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-{{ $stat['icon'] }} text-{{ $stat['color'] }}-500 text-lg"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card p-4">
        <form method="GET" action="{{ route('media.index') }}" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-48">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by title, creator, genre..."
                           class="form-input pl-9 text-sm">
                </div>
            </div>

            {{-- Type filter --}}
            <div class="flex gap-1 flex-wrap">
                <a href="{{ request()->except('type') ? url()->current() . '?' . http_build_query(request()->except('type')) : route('media.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl transition {{ !request('type') ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    All
                </a>
                @foreach(\App\Models\MediaItem::$typeIcons as $type => $icon)
                <a href="{{ request()->fullUrlWithQuery(['type' => $type]) }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl transition {{ request('type') === $type ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    {{ $icon }} {{ ucfirst(str_replace('_', ' ', $type)) }}
                </a>
                @endforeach
            </div>

            {{-- Status filter --}}
            <select name="status" class="form-input w-auto text-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(\App\Models\MediaItem::$statusLabels as $key => $label)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="sort" class="form-input w-auto text-sm" onchange="this.form.submit()">
                <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>Sort: Title</option>
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Sort: Newest</option>
                <option value="rating" {{ $sort === 'rating' ? 'selected' : '' }}>Sort: My Rating</option>
                <option value="year" {{ $sort === 'year' ? 'selected' : '' }}>Sort: Release Year</option>
            </select>

            <button type="submit" class="btn-primary text-sm">
                <i class="fas fa-search"></i>
            </button>
            @if(request()->anyFilled(['search', 'status', 'type']))
            <a href="{{ route('media.index') }}" class="btn-secondary text-sm">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </form>
    </div>

    {{-- Grid --}}
    @if($items->isEmpty())
    <div class="card p-12 text-center">
        <div class="text-6xl mb-4">🎬</div>
        <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-300">Your collection is empty</h3>
        <p class="text-gray-400 mt-2">Start adding movies, music, games, and more!</p>
        <a href="{{ route('media.create') }}" class="btn-primary mt-4 inline-flex">
            <i class="fas fa-plus"></i> Add First Item
        </a>
    </div>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        @foreach($items as $item)
        <div class="card overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 group"
             x-data="{ menuOpen: false }">
            {{-- Cover --}}
            <a href="{{ route('media.show', $item) }}" class="block">
                <div class="aspect-[3/4] relative overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                    @if($item->cover_image)
                    <img src="{{ $item->cover_url }}" alt="{{ $item->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-4xl">{{ $item->type_icon }}</div>
                    @endif

                    {{-- Status badge --}}
                    <div class="absolute top-2 left-2">
                        <span class="badge bg-{{ $item->status_color }}-500 text-white shadow-sm text-xs">
                            {{ $item->status_label }}
                        </span>
                    </div>

                    {{-- Favorite --}}
                    @if($item->is_favorite)
                    <div class="absolute top-2 right-2">
                        <span class="text-yellow-400 text-sm drop-shadow">★</span>
                    </div>
                    @endif
                </div>
            </a>

            {{-- Info --}}
            <div class="p-3">
                <a href="{{ route('media.show', $item) }}" class="block">
                    <p class="font-semibold text-gray-800 dark:text-white text-xs line-clamp-2 leading-tight">{{ $item->title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $item->creator }}</p>
                </a>

                <div class="flex items-center justify-between mt-2">
                    @if($item->rating)
                    <div class="flex items-center gap-1">
                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                        <span class="text-xs text-gray-500">{{ $item->rating }}/10</span>
                    </div>
                    @else
                    <span class="text-xs text-gray-300">{{ $item->release_year ?? '' }}</span>
                    @endif

                    {{-- Quick status change --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 px-1">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false"
                             class="absolute bottom-full right-0 mb-1 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1 z-20 min-w-36">
                            @foreach(\App\Models\MediaItem::$statusLabels as $key => $label)
                            <button class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 {{ $item->status === $key ? 'font-semibold text-blue-600' : '' }}"
                                    @click="updateStatus('{{ $item->id }}', '{{ $key }}'); open = false">
                                {{ $label }}
                            </button>
                            @endforeach
                            <hr class="my-1 border-gray-100 dark:border-gray-700">
                            <a href="{{ route('media.edit', $item) }}" class="block px-3 py-1.5 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                            <form method="POST" action="{{ route('media.destroy', $item) }}"
                                  onsubmit="return confirm('Remove from collection?')">
                                @csrf @method('DELETE')
                                <button class="w-full text-left px-3 py-1.5 text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <i class="fas fa-trash mr-1"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $items->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function updateStatus(id, status) {
    const res = await fetch(`/media/${id}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status })
    });
    if (res.ok) window.location.reload();
}
</script>
@endpush

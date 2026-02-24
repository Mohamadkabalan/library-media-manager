@extends('layouts.app')
@section('title', $mediaItem->title)
@section('page-title', 'Media Details')

@section('content')
<div class="space-y-6">
    <a href="{{ route('media.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600">
        <i class="fas fa-arrow-left"></i> Back to collection
    </a>

    <div class="card overflow-hidden">
        <div class="md:flex">
            {{-- Cover --}}
            <div class="md:w-64 flex-shrink-0">
                @if($mediaItem->cover_image)
                <img src="{{ $mediaItem->cover_url }}" class="w-full h-80 md:h-full object-cover" alt="">
                @else
                <div class="w-full h-64 md:h-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 flex items-center justify-center text-8xl">
                    {{ $mediaItem->type_icon }}
                </div>
                @endif
            </div>

            <div class="flex-1 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-2xl">{{ $mediaItem->type_icon }}</span>
                            <span class="badge bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs">{{ ucfirst(str_replace('_', ' ', $mediaItem->type)) }}</span>
                            @if($mediaItem->is_favorite)
                            <span class="badge bg-yellow-100 text-yellow-700">⭐ Favorite</span>
                            @endif
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $mediaItem->title }}</h1>
                        <p class="text-gray-500 mt-1">by {{ $mediaItem->creator }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('media.edit', $mediaItem) }}" class="btn-secondary text-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('media.destroy', $mediaItem) }}" onsubmit="return confirm('Remove this item?')">
                            @csrf @method('DELETE')
                            <button class="btn-danger text-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                    @if($mediaItem->genre)<div class="text-sm"><p class="text-gray-400 text-xs">Genre</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $mediaItem->genre }}</p></div>@endif
                    @if($mediaItem->release_year)<div class="text-sm"><p class="text-gray-400 text-xs">Year</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $mediaItem->release_year }}</p></div>@endif
                    @if($mediaItem->platform)<div class="text-sm"><p class="text-gray-400 text-xs">Platform</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $mediaItem->platform }}</p></div>@endif
                    @if($mediaItem->duration_minutes)<div class="text-sm"><p class="text-gray-400 text-xs">Duration</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $mediaItem->duration_minutes }}m</p></div>@endif
                    @if($mediaItem->started_at)<div class="text-sm"><p class="text-gray-400 text-xs">Started</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $mediaItem->started_at->format('M d, Y') }}</p></div>@endif
                    @if($mediaItem->completed_at)<div class="text-sm"><p class="text-gray-400 text-xs">Completed</p><p class="font-medium text-gray-700 dark:text-gray-200">{{ $mediaItem->completed_at->format('M d, Y') }}</p></div>@endif
                </div>

                {{-- Status + Rating --}}
                <div class="flex items-center gap-4 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <p class="text-xs text-gray-400">Status</p>
                        <span class="badge bg-{{ $mediaItem->status_color }}-100 text-{{ $mediaItem->status_color }}-700 font-medium mt-0.5">
                            {{ $mediaItem->status_label }}
                        </span>
                    </div>
                    @if($mediaItem->rating)
                    <div class="h-10 w-px bg-gray-200 dark:bg-gray-600"></div>
                    <div>
                        <p class="text-xs text-gray-400">My Rating</p>
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $mediaItem->rating }}/10</span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Quick status change --}}
                <div class="flex flex-wrap gap-2" x-data>
                    @foreach(\App\Models\MediaItem::$statusLabels as $key => $label)
                    <button @click="updateStatus('{{ $mediaItem->id }}', '{{ $key }}')"
                            class="text-xs px-3 py-1.5 rounded-xl border transition {{ $mediaItem->status === $key ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-blue-400' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-4">
            {{-- AI Summary --}}
            @if($mediaItem->ai_summary)
            <div class="card p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-robot text-purple-500"></i> AI Summary
                    </h3>
                    <form method="POST" action="{{ route('media.ai-summary', $mediaItem) }}">
                        @csrf
                        <button class="text-xs text-gray-400 hover:text-purple-600"><i class="fas fa-sync-alt mr-1"></i>Regen</button>
                    </form>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $mediaItem->ai_summary }}</p>
            </div>
            @else
            <div class="card p-4 border-dashed border-2 border-purple-200 dark:border-purple-800">
                <form method="POST" action="{{ route('media.ai-summary', $mediaItem) }}">
                    @csrf
                    <button class="text-sm text-purple-600 hover:underline">
                        <i class="fas fa-robot mr-1"></i>Generate AI Summary
                    </button>
                </form>
            </div>
            @endif

            @if($mediaItem->description)
            <div class="card p-6">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-3">Description</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $mediaItem->description }}</p>
            </div>
            @endif

            @if($mediaItem->personal_notes)
            <div class="card p-6 border-l-4 border-yellow-400">
                <h3 class="font-semibold text-gray-800 dark:text-white mb-3">📝 My Notes</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $mediaItem->personal_notes }}</p>
            </div>
            @endif

            @if(!empty($mediaItem->tags))
            <div class="card p-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($mediaItem->tags as $tag)
                    <span class="badge bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 px-3 py-1 rounded-full">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- AI Recommendations --}}
        @if(!empty($recommendations))
        <div class="card p-4">
            <h3 class="font-semibold text-gray-800 dark:text-white text-sm mb-3">
                <i class="fas fa-robot text-purple-500 mr-1"></i>You Might Also Like
            </h3>
            <div class="space-y-3">
                @foreach($recommendations as $rec)
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-xs font-semibold text-gray-800 dark:text-white">{{ $rec['title'] }}</p>
                    <p class="text-xs text-gray-500">{{ $rec['creator'] }}</p>
                    @if(!empty($rec['genre']))<span class="badge bg-purple-100 text-purple-700 text-xs mt-1">{{ $rec['genre'] }}</span>@endif
                    <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $rec['reason'] }}</p>
                    <form method="POST" action="{{ route('media.store') }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="title" value="{{ $rec['title'] }}">
                        <input type="hidden" name="creator" value="{{ $rec['creator'] }}">
                        <input type="hidden" name="type" value="{{ $mediaItem->type }}">
                        <input type="hidden" name="status" value="wishlist">
                        <input type="hidden" name="genre" value="{{ $rec['genre'] ?? '' }}">
                        <button class="text-xs text-purple-600 hover:underline">+ Add to wishlist</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
async function updateStatus(id, status) {
    const res = await fetch(`/media/${id}/status`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ status })
    });
    if (res.ok) window.location.reload();
}
</script>
@endpush

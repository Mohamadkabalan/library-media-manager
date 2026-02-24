@extends('layouts.app')
@section('title', isset($mediaItem) ? 'Edit: ' . $mediaItem->title : 'Add Media')
@section('page-title', isset($mediaItem) ? 'Edit Media' : 'Add to Collection')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                {{ isset($mediaItem) ? 'Edit: ' . $mediaItem->title : 'Add to My Collection' }}
            </h2>
            <a href="{{ route('media.index') }}" class="btn-secondary text-sm">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>

        @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- AI Auto-fill (create only) --}}
        @unless(isset($mediaItem))
        <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl"
             x-data="mediaAiEnrich()">
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-robot text-purple-600"></i>
                <h3 class="font-semibold text-purple-800 dark:text-purple-300 text-sm">AI Auto-Fill</h3>
            </div>
            <div class="flex gap-2 flex-wrap">
                <input x-model="aiTitle" type="text" placeholder="Title" class="form-input flex-1 text-sm min-w-32">
                <input x-model="aiCreator" type="text" placeholder="Director / Artist / Developer" class="form-input flex-1 text-sm min-w-32">
                <select x-model="aiType" class="form-input w-auto text-sm">
                    <option value="movie">Movie</option>
                    <option value="music">Music</option>
                    <option value="game">Game</option>
                    <option value="tv_show">TV Show</option>
                    <option value="podcast">Podcast</option>
                </select>
                <button @click="enrich()" :disabled="loading" class="btn-primary text-sm">
                    <i class="fas fa-magic" :class="{ 'fa-spin': loading }"></i>
                    <span x-text="loading ? 'Thinking...' : 'AI Fill'"></span>
                </button>
            </div>
            <p x-show="error" x-cloak class="text-xs text-red-500 mt-2" x-text="error"></p>
        </div>
        @endunless

        <form method="POST"
              action="{{ isset($mediaItem) ? route('media.update', $mediaItem) : route('media.store') }}"
              enctype="multipart/form-data">
            @csrf
            @isset($mediaItem) @method('PUT') @endisset

            <div class="grid md:grid-cols-2 gap-5">
                {{-- Title --}}
                <div class="md:col-span-2">
                    <label class="form-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="media-title"
                           value="{{ old('title', $mediaItem->title ?? '') }}" required
                           class="form-input" placeholder="Title">
                </div>

                {{-- Creator --}}
                <div>
                    <label class="form-label">Creator / Director / Artist <span class="text-red-500">*</span></label>
                    <input type="text" name="creator" id="media-creator"
                           value="{{ old('creator', $mediaItem->creator ?? '') }}" required
                           class="form-input" placeholder="Name">
                </div>

                {{-- Type --}}
                <div>
                    <label class="form-label">Type <span class="text-red-500">*</span></label>
                    <select name="type" id="media-type" class="form-input" required>
                        @foreach(\App\Models\MediaItem::$types as $type)
                        <option value="{{ $type }}" {{ old('type', $mediaItem->type ?? '') === $type ? 'selected' : '' }}>
                            {{ \App\Models\MediaItem::$typeIcons[$type] }} {{ ucfirst(str_replace('_', ' ', $type)) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Genre --}}
                <div>
                    <label class="form-label">Genre</label>
                    <input type="text" name="genre" id="media-genre"
                           value="{{ old('genre',  isset($mediaItem) ? $mediaItem->genre : '') }}"
                           class="form-input" placeholder="Action, Drama, RPG...">
                </div>

                {{-- Release Year --}}
                <div>
                    <label class="form-label">Release Year</label>
                    <input type="number" name="release_year" id="media-year"
                           value="{{ old('release_year',  isset($mediaItem) ? $mediaItem->release_year : '') }}"
                           min="1900" max="{{ date('Y') + 2 }}"
                           class="form-input" placeholder="{{ date('Y') }}">
                </div>

                {{-- Platform --}}
                <div>
                    <label class="form-label">Platform / Where to Find</label>
                    <input type="text" name="platform" id="media-platform"
                           value="{{ old('platform',  isset($mediaItem) ? $mediaItem->platform : '') }}"
                           class="form-input" placeholder="Netflix, Steam, Spotify...">
                </div>

                {{-- Duration --}}
                <div>
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="media-duration"
                           value="{{ old('duration_minutes',  isset($mediaItem) ? $mediaItem->duration_minutes : '') }}"
                           min="1" class="form-input" placeholder="120">
                </div>

                {{-- Status --}}
                <div>
                    <label class="form-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" class="form-input" required>
                        @foreach(\App\Models\MediaItem::$statusLabels as $key => $label)
                        <option value="{{ $key }}" {{ old('status',  isset($mediaItem) ? $mediaItem->status : 'wishlist') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Rating --}}
                <div>
                    <label class="form-label">My Rating (1-10)</label>
                    <input type="number" name="rating"
                           value="{{ old('rating', isset($mediaItem) ? $mediaItem->rating : '') }}"
                           min="1" max="10" class="form-input" placeholder="8">
                </div>

                {{-- Dates --}}
                <div>
                    <label class="form-label">Started</label>
                    <input type="date" name="started_at"
                           value="{{ old('started_at', isset($mediaItem) ? $mediaItem->started_at?->format('Y-m-d') : '') }}"
                           class="form-input">
                </div>
                <div>
                    <label class="form-label">Completed</label>
                    <input type="date" name="completed_at"
                           value="{{ old('completed_at', isset($mediaItem) ? $mediaItem->completed_at?->format('Y-m-d') : '' ) }}"
                           class="form-input">
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="media-description" rows="3"
                              class="form-input" placeholder="Brief description...">{{ old('description', isset($mediaItem) ?  $mediaItem->description : '') }}</textarea>
                </div>

                {{-- Personal Notes --}}
                <div class="md:col-span-2">
                    <label class="form-label">Personal Notes</label>
                    <textarea name="personal_notes" rows="2"
                              class="form-input" placeholder="Your thoughts, memorable scenes, etc...">{{ old('personal_notes', isset($mediaItem) ?  $mediaItem->personal_notes : '') }}</textarea>
                </div>

                {{-- Cover Image --}}
                <div class="md:col-span-2" x-data="{ preview: null }">
                    <label class="form-label">Cover / Poster Image</label>
                    @isset($mediaItem)
                    @if($mediaItem->cover_image)
                    <div class="mb-2"><img src="{{ $mediaItem->cover_url }}" class="h-24 object-cover rounded-lg"></div>
                    @endif
                    @endisset
                    <input type="file" name="cover_image" accept="image/*" class="form-input"
                           @change="preview = URL.createObjectURL($event.target.files[0])">
                    <img x-show="preview" :src="preview" class="mt-2 h-32 object-cover rounded-lg">
                </div>

                {{-- Favorite + AI Enrich --}}
                <div class="md:col-span-2 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_favorite" value="1"
                               {{ old('is_favorite', $mediaItem->is_favorite ?? false) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-yellow-500">
                        <span class="text-sm text-gray-700 dark:text-gray-200">⭐ Mark as Favorite</span>
                    </label>

                    @unless(isset($mediaItem))
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="ai_enrich" value="1" class="w-4 h-4 rounded text-purple-600">
                        <span class="text-sm text-gray-700 dark:text-gray-200">
                            <i class="fas fa-robot text-purple-500 mr-1"></i>Auto-fill missing fields with AI
                        </span>
                    </label>
                    @endunless
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> {{ isset($mediaItem) ? 'Update' : 'Add to Collection' }}
                </button>
                <a href="{{ route('media.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function mediaAiEnrich() {
    return {
        aiTitle: '', aiCreator: '', aiType: 'movie',
        loading: false, error: '',

        async enrich() {
            if (!this.aiTitle || !this.aiCreator) { this.error = 'Enter title and creator.'; return; }
            this.loading = true; this.error = '';
            try {
                const res = await fetch('{{ route("media.ai-enrich") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ title: this.aiTitle, creator: this.aiCreator, type: this.aiType })
                });
                const d = await res.json();
                if (d.genre) document.getElementById('media-genre').value = d.genre;
                if (d.release_year) document.getElementById('media-year').value = d.release_year;
                if (d.description) document.getElementById('media-description').value = d.description;
                if (d.platform) document.getElementById('media-platform').value = d.platform;
                if (d.duration_minutes) document.getElementById('media-duration').value = d.duration_minutes;
                if (!document.getElementById('media-title').value) document.getElementById('media-title').value = this.aiTitle;
                if (!document.getElementById('media-creator').value) document.getElementById('media-creator').value = this.aiCreator;
                document.getElementById('media-type').value = this.aiType;
            } catch(e) { this.error = 'AI fill failed.'; }
            this.loading = false;
        }
    }
}
</script>
@endpush

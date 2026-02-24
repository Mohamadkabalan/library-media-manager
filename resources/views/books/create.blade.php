@extends('layouts.app')
@section('title', isset($book) ? 'Edit Book' : 'Add Book')
@section('page-title', isset($book) ? 'Edit Book' : 'Add New Book')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                {{ isset($book) ? 'Edit: ' . $book->title : 'Add New Book' }}
            </h2>
            <a href="{{ route('books.index') }}" class="btn-secondary text-sm">
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

        {{-- AI Enrich Tool --}}
        @unless(isset($book))
        <div class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl" x-data="aiEnrich()">
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-robot text-purple-600"></i>
                <h3 class="font-semibold text-purple-800 dark:text-purple-300 text-sm">AI Auto-Fill</h3>
                <span class="badge bg-purple-200 text-purple-700 text-xs">New!</span>
            </div>
            <p class="text-xs text-purple-600 dark:text-purple-400 mb-3">Enter a title and author, then let AI fill in the rest automatically.</p>
            <div class="flex gap-2">
                <input x-model="aiTitle" type="text" placeholder="Book title" class="form-input flex-1 text-sm">
                <input x-model="aiAuthor" type="text" placeholder="Author name" class="form-input flex-1 text-sm">
                <button @click="enrich()" :disabled="loading" class="btn-primary text-sm flex-shrink-0">
                    <i class="fas fa-magic" :class="{ 'fa-spin': loading }"></i>
                    <span x-text="loading ? 'Thinking...' : 'AI Fill'"></span>
                </button>
            </div>
            <p x-show="error" x-cloak class="text-xs text-red-500 mt-2" x-text="error"></p>
        </div>
        @endunless

        <form method="POST"
              action="{{ isset($book) ? route('books.update', $book) : route('books.store') }}"
              enctype="multipart/form-data"
              x-data="bookForm()">
            @csrf
            @isset($book) @method('PUT') @endisset

            <div class="grid md:grid-cols-2 gap-5">
                {{-- Title --}}
                <div class="md:col-span-2">
                    <label class="form-label">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title-field"
                           value="{{ old('title', $book->title ?? '') }}" required
                           class="form-input" placeholder="Book title">
                </div>

                {{-- Author --}}
                <div class="md:col-span-2">
                    <label class="form-label">Author <span class="text-red-500">*</span></label>
                    <input type="text" name="author" id="author-field"
                           value="{{ old('author', $book->author ?? '') }}" required
                           class="form-input" placeholder="Author name">
                </div>

                {{-- ISBN --}}
                <div>
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" value="{{ old('isbn', $book->isbn ?? '') }}"
                           class="form-input" placeholder="978-...">
                </div>

                {{-- Genre --}}
                <div>
                    <label class="form-label">Genre</label>
                    <input type="text" name="genre" id="genre-field"
                           value="{{ old('genre', $book->genre ?? '') }}"
                           class="form-input" placeholder="Fiction, Mystery, Science...">
                </div>

                {{-- Publisher --}}
                <div>
                    <label class="form-label">Publisher</label>
                    <input type="text" name="publisher" id="publisher-field"
                           value="{{ old('publisher', $book->publisher ?? '') }}"
                           class="form-input" placeholder="Publisher name">
                </div>

                {{-- Year --}}
                <div>
                    <label class="form-label">Publication Year</label>
                    <input type="number" name="publication_year" id="pub-year-field"
                           value="{{ old('publication_year', $book->publication_year ?? '') }}"
                           min="1000" max="{{ date('Y') + 1 }}"
                           class="form-input" placeholder="{{ date('Y') }}">
                </div>

                {{-- Pages --}}
                <div>
                    <label class="form-label">Total Pages</label>
                    <input type="number" name="total_pages" id="pages-field"
                           value="{{ old('total_pages', $book->total_pages ?? '') }}"
                           min="1" class="form-input" placeholder="250">
                </div>

                {{-- Language --}}
                <div>
                    <label class="form-label">Language</label>
                    <input type="text" name="language"
                           value="{{ old('language', $book->language ?? 'English') }}"
                           class="form-input" placeholder="English">
                </div>

                {{-- Copies --}}
                <div>
                    <label class="form-label">Total Copies <span class="text-red-500">*</span></label>
                    <input type="number" name="total_copies"
                           value="{{ old('total_copies', $book->total_copies ?? 1) }}"
                           min="{{ isset($book) ? ($book->total_copies - $book->available_copies) : 1 }}"
                           required class="form-input">
                </div>

                {{-- Location --}}
                <div>
                    <label class="form-label">Shelf Location</label>
                    <input type="text" name="location"
                           value="{{ old('location', $book->location ?? '') }}"
                           class="form-input" placeholder="A3, Section B...">
                </div>

                {{-- Status (edit only) --}}
                @isset($book)
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active" {{ $book->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ $book->status === 'archived' ? 'selected' : '' }}>Archived</option>
                        <option value="lost" {{ $book->status === 'lost' ? 'selected' : '' }}>Lost</option>
                    </select>
                </div>
                @endisset

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="description-field" rows="4"
                              class="form-input" placeholder="Brief description of the book...">{{ old('description', $book->description ?? '') }}</textarea>
                </div>

                {{-- Cover Image --}}
                <div class="md:col-span-2" x-data="{ preview: null }">
                    <label class="form-label">Cover Image</label>
                    @isset($book)
                    @if($book->cover_image)
                    <div class="mb-2">
                        <img src="{{ $book->cover_url }}" class="h-24 object-cover rounded-lg">
                        <p class="text-xs text-gray-400 mt-1">Current cover. Upload new to replace.</p>
                    </div>
                    @endif
                    @endisset
                    <input type="file" name="cover_image" accept="image/*" class="form-input"
                           @change="preview = URL.createObjectURL($event.target.files[0])">
                    <img x-show="preview" :src="preview" class="mt-2 h-32 object-cover rounded-lg">
                </div>

                {{-- AI Enrich checkbox (create only) --}}
                @unless(isset($book))
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="ai_enrich" value="1" class="w-4 h-4 rounded text-purple-600">
                        <span class="text-sm text-gray-700 dark:text-gray-200">
                            <i class="fas fa-robot text-purple-500 mr-1"></i>
                            Auto-fill missing fields with AI (uses GPT)
                        </span>
                    </label>
                </div>
                @endunless
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> {{ isset($book) ? 'Update Book' : 'Add Book' }}
                </button>
                <a href="{{ route('books.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bookForm() {
    return {}
}

function aiEnrich() {
    return {
        aiTitle: '',
        aiAuthor: '',
        loading: false,
        error: '',

        async enrich() {
            if (!this.aiTitle || !this.aiAuthor) {
                this.error = 'Please enter both title and author.';
                return;
            }
            this.loading = true;
            this.error = '';

            try {
                const res = await fetch('{{ route("books.ai-enrich") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ title: this.aiTitle, author: this.aiAuthor })
                });
                const data = await res.json();

                // Fill form fields
                if (data.genre) document.getElementById('genre-field').value = data.genre;
                if (data.publisher) document.getElementById('publisher-field').value = data.publisher;
                if (data.publication_year) document.getElementById('pub-year-field').value = data.publication_year;
                if (data.total_pages) document.getElementById('pages-field').value = data.total_pages;
                if (data.description) document.getElementById('description-field').value = data.description;
                if (!document.getElementById('title-field').value) document.getElementById('title-field').value = this.aiTitle;
                if (!document.getElementById('author-field').value) document.getElementById('author-field').value = this.aiAuthor;

            } catch(e) {
                this.error = 'AI enrichment failed. Please fill manually.';
            }
            this.loading = false;
        }
    }
}
</script>
@endpush

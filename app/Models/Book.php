<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'author', 'isbn', 'genre', 'description', 'ai_summary',
        'ai_tags', 'publisher', 'publication_year', 'total_pages', 'language',
        'cover_image', 'location', 'total_copies', 'available_copies',
        'average_rating', 'times_borrowed', 'status', 'added_by',
    ];

    protected $casts = [
        'ai_tags' => 'array',
        'available_copies' => 'integer',
        'total_copies' => 'integer',
        'average_rating' => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(BookCheckout::class);
    }

    public function activeCheckouts(): HasMany
    {
        return $this->hasMany(BookCheckout::class)->where('status', 'active');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(BookRating::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(BookReservation::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('available_copies', '>', 0)->where('status', 'active');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('author', 'LIKE', "%{$term}%")
              ->orWhere('isbn', 'LIKE', "%{$term}%")
              ->orWhere('genre', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%")
              ->orWhere('publisher', 'LIKE', "%{$term}%");
        });
    }

    public function scopeByGenre(Builder $query, string $genre): Builder
    {
        return $query->where('genre', $genre);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getIsAvailableAttribute(): bool
    {
        return $this->available_copies > 0 && $this->status === 'active';
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        // Return Open Library cover if ISBN exists
        if ($this->isbn) {
            return "https://covers.openlibrary.org/b/isbn/{$this->isbn}-M.jpg";
        }
        return asset('images/no-cover.png');
    }

    public function getAiTagsListAttribute(): array
    {
        return $this->ai_tags ?? [];
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    public function checkOut(User $user, int $days = 14, ?User $librarian = null): BookCheckout
    {
        if ($this->available_copies <= 0) {
            throw new \Exception('No copies available for checkout.');
        }

        $checkout = $this->checkouts()->create([
            'user_id' => $user->id,
            'checked_out_at' => now(),
            'due_date' => now()->addDays($days),
            'status' => 'active',
            'checked_out_by' => $librarian?->id ?? $user->id,
        ]);

        $this->decrement('available_copies');
        $this->increment('times_borrowed');

        return $checkout;
    }

    public function checkIn(BookCheckout $checkout, ?User $librarian = null): void
    {
        $checkout->update([
            'returned_at' => now(),
            'status' => 'returned',
            'checked_in_by' => $librarian?->id,
        ]);

        $this->increment('available_copies');
    }

    public function updateAverageRating(): void
    {
        $avg = $this->ratings()->avg('rating');
        $this->update(['average_rating' => round($avg ?? 0, 2)]);
    }
}

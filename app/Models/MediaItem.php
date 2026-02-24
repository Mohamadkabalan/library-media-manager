<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class MediaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'creator', 'type', 'genre', 'release_date',
        'release_year', 'description', 'ai_summary', 'cover_image', 'external_id',
        'platform', 'language', 'duration_minutes', 'status', 'rating',
        'personal_notes', 'is_favorite', 'started_at', 'completed_at',
        'play_count', 'tags', 'ai_recommendations_cache',
    ];

    protected $casts = [
        'release_date' => 'date',
        'started_at' => 'date',
        'completed_at' => 'date',
        'tags' => 'array',
        'ai_recommendations_cache' => 'array',
        'is_favorite' => 'boolean',
        'rating' => 'integer',
    ];

    public static array $types = ['movie', 'music', 'game', 'tv_show', 'podcast', 'book'];
    public static array $statuses = ['owned', 'wishlist', 'currently_using', 'completed', 'dropped'];

    public static array $statusLabels = [
        'owned' => 'Owned',
        'wishlist' => 'Wishlist',
        'currently_using' => 'Currently Using',
        'completed' => 'Completed',
        'dropped' => 'Dropped',
    ];

    public static array $statusColors = [
        'owned' => 'blue',
        'wishlist' => 'yellow',
        'currently_using' => 'green',
        'completed' => 'purple',
        'dropped' => 'gray',
    ];

    public static array $typeIcons = [
        'movie' => '🎬',
        'music' => '🎵',
        'game' => '🎮',
        'tv_show' => '📺',
        'podcast' => '🎙️',
        'book' => '📚',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(MediaCollection::class, 'media_collection_items')
                    ->withPivot('order')->withTimestamps();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('creator', 'LIKE', "%{$term}%")
              ->orWhere('genre', 'LIKE', "%{$term}%")
              ->orWhere('platform', 'LIKE', "%{$term}%")
              ->orWhere('description', 'LIKE', "%{$term}%");
        });
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image && str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return asset('images/no-cover.png');
    }

    public function getTypeIconAttribute(): string
    {
        return self::$typeIcons[$this->type] ?? '📦';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::$statusColors[$this->status] ?? 'gray';
    }
}

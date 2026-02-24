<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaCollection extends Model
{
    protected $fillable = ['user_id', 'name', 'description', 'is_public', 'cover_image'];

    protected $casts = ['is_public' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(MediaItem::class, 'media_collection_items')
                    ->withPivot('order')->withTimestamps()->orderByPivot('order');
    }
}

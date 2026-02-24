<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'provider',
        'provider_id', 'provider_token', 'bio', 'is_active', 'profile_public',
    ];

    protected $hidden = [
        'password', 'remember_token', 'provider_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'profile_public' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function books()
    {
        return $this->hasMany(Book::class, 'added_by');
    }

    public function checkouts()
    {
        return $this->hasMany(BookCheckout::class);
    }

    public function activeCheckouts()
    {
        return $this->hasMany(BookCheckout::class)->where('status', 'active');
    }

    public function ratings()
    {
        return $this->hasMany(BookRating::class);
    }

    public function mediaItems()
    {
        return $this->hasMany(MediaItem::class);
    }

    public function mediaCollections()
    {
        return $this->hasMany(MediaCollection::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }

    public function getIsLibrarianAttribute(): bool
    {
        return $this->hasRole('librarian') || $this->hasRole('admin');
    }
}

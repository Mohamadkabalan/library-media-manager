<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCheckout extends Model
{
    protected $fillable = [
        'book_id', 'user_id', 'checked_out_at', 'due_date', 'returned_at',
        'status', 'renewal_count', 'notes', 'checked_out_by', 'checked_in_by',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'due_date' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active' && $this->due_date->isPast();
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== 'active') return 0;
        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function renew(int $days = 14): void
    {
        if ($this->renewal_count >= 3) {
            throw new \Exception('Maximum renewals reached.');
        }
        $this->update([
            'due_date' => $this->due_date->addDays($days),
            'renewal_count' => $this->renewal_count + 1,
        ]);
    }
}

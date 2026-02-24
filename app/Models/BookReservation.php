<?php

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BookReservation extends Model
{
    protected $fillable = ['book_id', 'user_id', 'status', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

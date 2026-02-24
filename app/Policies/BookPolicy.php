<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function create(User $user): bool
    {
        return $user->is_librarian;
    }

    public function update(User $user, Book $book): bool
    {
        return $user->is_librarian;
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->is_librarian;
    }

    public function checkout(User $user, Book $book): bool
    {
        return $user->is_active;
    }

    public function checkin(User $user, Book $book): bool
    {
        return $user->is_active;
    }
}

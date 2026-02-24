<?php
namespace App\Providers;

use App\Models\Book;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
      Book::class => BookPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        Paginator::useTailwind();
    }
}
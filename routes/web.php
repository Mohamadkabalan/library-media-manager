<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\MediaItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ────────────────────────────────────────────────────────────

Route::get('/', function () {
    if (auth()->check()) return redirect()->route('dashboard');
    return redirect()->route('login');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // SSO
    Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirectToProvider'])->name('auth.sso');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('auth.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Authenticated Routes ─────────────────────────────────────────────────────

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Books (Version 1) ───────────────────────────────────────────────────
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // Book checkout/checkin
    Route::post('/books/{book}/checkout', [BookController::class, 'checkout'])->name('books.checkout');
    Route::post('/books/{book}/checkin', [BookController::class, 'checkin'])->name('books.checkin');
    Route::post('/books/{book}/renew', [BookController::class, 'renewCheckout'])->name('books.renew');
    Route::post('/books/{book}/rate', [BookController::class, 'rate'])->name('books.rate');

    // Book AI actions
    Route::post('/books/{book}/ai-summary', [BookController::class, 'generateSummary'])->name('books.ai-summary');
    Route::post('/books/{book}/ai-tags', [BookController::class, 'generateTags'])->name('books.ai-tags');
    Route::post('/books/ai-enrich', [BookController::class, 'aiEnrich'])->name('books.ai-enrich');

    // ── Media (Version 2) ───────────────────────────────────────────────────
    Route::get('/media', [MediaItemController::class, 'index'])->name('media.index');
    Route::get('/media/create', [MediaItemController::class, 'create'])->name('media.create');
    Route::post('/media', [MediaItemController::class, 'store'])->name('media.store');
    Route::get('/media/{mediaItem}', [MediaItemController::class, 'show'])->name('media.show');
    Route::get('/media/{mediaItem}/edit', [MediaItemController::class, 'edit'])->name('media.edit');
    Route::put('/media/{mediaItem}', [MediaItemController::class, 'update'])->name('media.update');
    Route::delete('/media/{mediaItem}', [MediaItemController::class, 'destroy'])->name('media.destroy');
    Route::patch('/media/{mediaItem}/status', [MediaItemController::class, 'updateStatus'])->name('media.status');
    Route::post('/media/{mediaItem}/ai-summary', [MediaItemController::class, 'generateSummary'])->name('media.ai-summary');
    Route::post('/media/ai-enrich', [MediaItemController::class, 'aiEnrich'])->name('media.ai-enrich');

    // ── AI Chat ─────────────────────────────────────────────────────────────
    Route::get('/ai-assistant', [AiChatController::class, 'index'])->name('ai.chat');
    Route::post('/ai-assistant/chat', [AiChatController::class, 'chat'])->name('ai.chat.post');
    Route::get('/ai-assistant/recommendations', [AiChatController::class, 'recommendations'])->name('ai.recommendations');

    // ── Admin ───────────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
        Route::post('/users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
        Route::get('/overdue', [AdminController::class, 'overdueReport'])->name('overdue');
        Route::get('/stats', [AdminController::class, 'stats'])->name('stats');
    });
});

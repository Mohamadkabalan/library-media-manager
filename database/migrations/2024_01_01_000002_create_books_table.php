<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn', 20)->nullable()->unique();
            $table->string('genre')->nullable();
            $table->text('description')->nullable();
            $table->text('ai_summary')->nullable(); // AI-generated summary
            $table->text('ai_tags')->nullable(); // AI-generated tags JSON
            $table->string('publisher')->nullable();
            $table->integer('publication_year')->nullable();
            $table->integer('total_pages')->nullable();
            $table->string('language')->default('English');
            $table->string('cover_image')->nullable();
            $table->string('location')->nullable(); // Shelf location
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('times_borrowed')->default(0);
            $table->enum('status', ['active', 'archived', 'lost'])->default('active');
            $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->fullText(['title', 'author', 'description', 'genre']);
            $table->index(['genre', 'status']);
            $table->index('author');
        });

        Schema::create('book_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_out_at');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue', 'lost'])->default('active');
            $table->integer('renewal_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['book_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('book_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating'); // 1-5
            $table->text('review')->nullable();
            $table->timestamps();
            $table->unique(['book_id', 'user_id']);
        });

        Schema::create('book_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'ready', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['book_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_reservations');
        Schema::dropIfExists('book_ratings');
        Schema::dropIfExists('book_checkouts');
        Schema::dropIfExists('books');
    }
};

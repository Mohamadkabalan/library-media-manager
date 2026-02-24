<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('creator'); // director/artist/developer
            $table->enum('type', ['movie', 'music', 'game', 'tv_show', 'podcast', 'book'])->default('movie');
            $table->string('genre')->nullable();
            $table->date('release_date')->nullable();
            $table->integer('release_year')->nullable();
            $table->text('description')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('external_id')->nullable(); // TMDB/IGDB/MusicBrainz ID
            $table->string('platform')->nullable(); // Netflix, Steam, Spotify, etc.
            $table->string('language')->nullable();
            $table->integer('duration_minutes')->nullable(); // movie runtime / album length
            $table->enum('status', ['owned', 'wishlist', 'currently_using', 'completed', 'dropped'])->default('wishlist');
            $table->tinyInteger('rating')->nullable(); // 1-10
            $table->text('personal_notes')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->integer('play_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('ai_recommendations_cache')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->fullText(['title', 'creator', 'description', 'genre']);
            $table->index(['user_id', 'type', 'status']);
            $table->index('type');
        });

        Schema::create('media_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('cover_image')->nullable();
            $table->timestamps();
        });

        Schema::create('media_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('media_collections')->cascadeOnDelete();
            $table->foreignId('media_item_id')->constrained('media_items')->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['collection_id', 'media_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_collection_items');
        Schema::dropIfExists('media_collections');
        Schema::dropIfExists('media_items');
    }
};

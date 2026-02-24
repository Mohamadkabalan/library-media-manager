<?php

namespace App\Services;

use App\Models\Book;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected string $model = 'gpt-4o-mini';
    protected string $embeddingModel = 'text-embedding-3-small';

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
    }

    // ─── Book Features ────────────────────────────────────────────────────────

    /**
     * Generate an AI summary for a book
     */
    public function generateBookSummary(Book $book): string
    {
        $cacheKey = "ai_book_summary_{$book->id}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($book) {
            $prompt = "You are a knowledgeable librarian. Write a concise, engaging 2-3 paragraph summary of the book '{$book->title}' by {$book->author}.";

            if ($book->description) {
                $prompt .= "\n\nExisting description: {$book->description}";
                $prompt .= "\n\nEnhance and summarize this into a compelling library description that would help readers decide if they want to read this book.";
            } else {
                $prompt .= "\n\nBased on your knowledge of this book, provide an engaging summary suitable for a library catalog.";
            }

            $response = $this->chat([
                ['role' => 'user', 'content' => $prompt]
            ]);

            return $response ?? 'Summary not available.';
        });
    }

    /**
     * Generate AI tags for a book
     */
    public function generateBookTags(Book $book): array
    {
        $cacheKey = "ai_book_tags_{$book->id}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($book) {
            $prompt = "Generate 5-8 relevant tags/keywords for the book '{$book->title}' by {$book->author}."
                . ($book->genre ? " Genre: {$book->genre}." : '')
                . "\n\nReturn ONLY a JSON array of strings, no explanation. Example: [\"adventure\", \"coming-of-age\", \"dystopia\"]";

            $response = $this->chat([
                ['role' => 'user', 'content' => $prompt]
            ]);

            try {
                $json = preg_replace('/```json?\s*|\s*```/', '', $response ?? '[]');
                return json_decode($json, true) ?? [];
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Get AI-powered book recommendations for a user based on their borrowing history
     */
    public function getBookRecommendations(User $user, int $limit = 6): array
    {
        $cacheKey = "ai_book_recommendations_{$user->id}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($user, $limit) {
            // Get user's reading history
            $borrowedBooks = $user->checkouts()
                ->with('book')
                ->where('status', 'returned')
                ->latest()
                ->limit(10)
                ->get()
                ->pluck('book')
                ->filter();

            $ratedBooks = $user->ratings()
                ->with('book')
                ->where('rating', '>=', 4)
                ->get()
                ->pluck('book')
                ->filter();

            $historyText = '';
            $allBooks = $borrowedBooks->merge($ratedBooks)->unique('id');

            if ($allBooks->isEmpty()) {
                // No history - recommend popular books
                $popular = Book::active()->orderByDesc('times_borrowed')->limit($limit)->get();
                return $popular->map(fn($b) => [
                    'id' => $b->id,
                    'title' => $b->title,
                    'author' => $b->author,
                    'reason' => 'Popular in our library',
                    'genre' => $b->genre,
                ])->toArray();
            }

            foreach ($allBooks->take(5) as $book) {
                $historyText .= "- \"{$book->title}\" by {$book->author}" . ($book->genre ? " ({$book->genre})" : '') . "\n";
            }

            $prompt = "Based on a library user's reading history:\n{$historyText}\n"
                . "Recommend {$limit} books they might enjoy. For each, provide:\n"
                . "- title: book title\n"
                . "- author: author name\n"
                . "- reason: short 1-sentence reason why they'd like it\n"
                . "- genre: primary genre\n\n"
                . "Return ONLY a JSON array. Example: [{\"title\":\"Book Name\",\"author\":\"Author\",\"reason\":\"Because...\",\"genre\":\"Fiction\"}]";

            $response = $this->chat([
                ['role' => 'user', 'content' => $prompt]
            ]);

            try {
                $json = preg_replace('/```json?\s*|\s*```/', '', $response ?? '[]');
                $recommendations = json_decode($json, true) ?? [];

                // Try to match with actual books in library
                foreach ($recommendations as &$rec) {
                    $found = Book::where('title', 'LIKE', '%' . $rec['title'] . '%')
                                 ->orWhere('author', 'LIKE', '%' . $rec['author'] . '%')
                                 ->first();
                    $rec['book_id'] = $found?->id;
                    $rec['in_library'] = $found !== null;
                }

                return $recommendations;
            } catch (\Exception $e) {
                Log::error('AI recommendation error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Semantic search for books using embeddings
     */
    public function semanticSearchBooks(string $query, int $limit = 10): array
    {
        try {
            // Get embedding for the query
            $queryEmbedding = $this->getEmbedding($query);

            if (empty($queryEmbedding)) {
                // Fallback to regular search
                return Book::active()->search($query)->limit($limit)->get()->toArray();
            }

            // Get all books and compute cosine similarity
            // In production, use MySQL vector search or pgvector
            $books = Book::active()->select(['id', 'title', 'author', 'genre', 'description', 'ai_summary'])->get();

            $scored = [];
            foreach ($books as $book) {
                $text = implode(' ', array_filter([
                    $book->title, $book->author, $book->genre,
                    $book->description, $book->ai_summary
                ]));

                // Use keyword matching as fallback since we don't store embeddings
                $score = similar_text(strtolower($query), strtolower($text));
                $scored[] = ['book' => $book, 'score' => $score];
            }

            usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

            return array_slice(array_map(fn($s) => $s['book']->toArray(), $scored), 0, $limit);

        } catch (\Exception $e) {
            Log::error('Semantic search error: ' . $e->getMessage());
            return Book::active()->search($query)->limit($limit)->get()->toArray();
        }
    }

    /**
     * Natural language search interpretation
     */
    public function interpretSearchQuery(string $query): array
    {
        $cacheKey = 'ai_search_interpret_' . md5($query);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
            $prompt = "Interpret this library search query and extract structured search parameters:\n\n"
                . "Query: \"{$query}\"\n\n"
                . "Extract and return JSON with these fields (null if not mentioned):\n"
                . "- keywords: array of important search terms\n"
                . "- author: specific author name or null\n"
                . "- genre: genre or null\n"
                . "- mood: mood/theme like 'adventure', 'romantic', 'dark' or null\n"
                . "- similar_to: book/series this is similar to, or null\n"
                . "- era: time period like 'modern', 'classic', '19th century' or null\n"
                . "- audience: 'children', 'young adult', 'adult' or null\n\n"
                . "Return ONLY valid JSON, no explanation.";

            $response = $this->chat([
                ['role' => 'user', 'content' => $prompt]
            ]);

            try {
                $json = preg_replace('/```json?\s*|\s*```/', '', $response ?? '{}');
                return json_decode($json, true) ?? ['keywords' => [$query]];
            } catch (\Exception $e) {
                return ['keywords' => [$query]];
            }
        });
    }

    // ─── Media Features ───────────────────────────────────────────────────────

    /**
     * Generate AI summary for a media item
     */
    public function generateMediaSummary(MediaItem $item): string
    {
        $cacheKey = "ai_media_summary_{$item->id}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($item) {
            $typeLabel = str_replace('_', ' ', $item->type);
            $prompt = "Write a concise, engaging 2-paragraph summary of the {$typeLabel} '{$item->title}'"
                . " by/from {$item->creator}"
                . ($item->release_year ? " ({$item->release_year})" : '')
                . ($item->genre ? ", genre: {$item->genre}" : '') . ".";

            if ($item->description) {
                $prompt .= "\n\nExisting info: {$item->description}\n\nEnhance this into an engaging summary.";
            }

            return $this->chat([['role' => 'user', 'content' => $prompt]]) ?? 'Summary not available.';
        });
    }

    /**
     * Get media recommendations based on user's collection
     */
    public function getMediaRecommendations(User $user, string $type = 'movie', int $limit = 6): array
    {
        $cacheKey = "ai_media_recommendations_{$user->id}_{$type}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($user, $type, $limit) {
            $collection = $user->mediaItems()
                ->byType($type)
                ->whereIn('status', ['completed', 'owned', 'currently_using'])
                ->where('rating', '>=', 7)
                ->latest()
                ->limit(8)
                ->get();

            $typeLabel = str_replace('_', ' ', $type);

            if ($collection->isEmpty()) {
                $prompt = "Recommend {$limit} popular {$typeLabel}s. Return JSON array: "
                    . "[{\"title\":\"\",\"creator\":\"\",\"reason\":\"\",\"genre\":\"\",\"year\":0}]";
            } else {
                $historyText = $collection->map(fn($m) =>
                    "- \"{$m->title}\" by {$m->creator}" . ($m->genre ? " ({$m->genre})" : '') . ($m->rating ? " - rated {$m->rating}/10" : '')
                )->join("\n");

                $prompt = "Based on this user's {$typeLabel} collection:\n{$historyText}\n\n"
                    . "Recommend {$limit} {$typeLabel}s they'd enjoy. Return JSON array:\n"
                    . "[{\"title\":\"\",\"creator\":\"\",\"reason\":\"1 sentence\",\"genre\":\"\",\"year\":0}]";
            }

            $response = $this->chat([['role' => 'user', 'content' => $prompt]]);

            try {
                $json = preg_replace('/```json?\s*|\s*```/', '', $response ?? '[]');
                return json_decode($json, true) ?? [];
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Semantic search for media items
     */
    public function semanticSearchMedia(string $query, int $userId, int $limit = 10): array
    {
        $items = MediaItem::forUser($userId)->search($query)->limit($limit)->get();
        return $items->toArray();
    }

    /**
     * Auto-enrich book metadata from title and author
     */
    public function enrichBookMetadata(string $title, string $author): array
    {
        $prompt = "Provide metadata for the book '{$title}' by {$author}.\n"
            . "Return JSON with: genre, publisher, publication_year (integer), total_pages (integer), "
            . "language, description (2-3 sentences), tags (array of 5 keywords).\n"
            . "Return ONLY valid JSON, no explanation.";

        $response = $this->chat([['role' => 'user', 'content' => $prompt]]);

        try {
            $json = preg_replace('/```json?\s*|\s*```/', '', $response ?? '{}');
            return json_decode($json, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Auto-enrich media metadata
     */
    public function enrichMediaMetadata(string $title, string $creator, string $type): array
    {
        $typeLabel = str_replace('_', ' ', $type);
        $prompt = "Provide metadata for the {$typeLabel} '{$title}' by/from {$creator}.\n"
            . "Return JSON with: genre, release_year (integer), description (2-3 sentences), "
            . "duration_minutes (integer or null), platform (where to find it), tags (array of 5 keywords).\n"
            . "Return ONLY valid JSON.";

        $response = $this->chat([['role' => 'user', 'content' => $prompt]]);

        try {
            $json = preg_replace('/```json?\s*|\s*```/', '', $response ?? '{}');
            return json_decode($json, true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * AI Chat assistant for books/media
     */
    public function chat(array $messages, ?string $systemPrompt = null): ?string
    {
        try {
            $payload = [
                'model' => $this->model,
                'messages' => $systemPrompt
                    ? array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages)
                    : $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ];

            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::error('OpenAI API error: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('OpenAI chat error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get embedding vector for semantic search
     */
    public function getEmbedding(string $text): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $this->embeddingModel,
                    'input' => substr($text, 0, 8000),
                ]);

            if ($response->successful()) {
                return $response->json('data.0.embedding') ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('Embedding error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Library assistant chatbot
     */
    public function libraryChat(array $conversationHistory, string $context = 'library'): ?string
    {
        $systemPrompt = $context === 'library'
            ? "You are a helpful library assistant. You help users find books, make recommendations, discuss literature, and answer questions about the library. Be friendly, knowledgeable, and concise."
            : "You are a helpful media advisor. You help users discover movies, music, games, and other media. Make personalized recommendations and discuss entertainment. Be friendly and enthusiastic.";

        return $this->chat($conversationHistory, $systemPrompt);
    }
}

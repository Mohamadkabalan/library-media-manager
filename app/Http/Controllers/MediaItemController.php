<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MediaItemController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function index(Request $request)
    {
        $query = MediaItem::forUser(Auth::id())->with('user');

        if ($search = $request->get('search')) {
            $query->search($search);
        }
        if ($type = $request->get('type')) {
            $query->byType($type);
        }
        if ($status = $request->get('status')) {
            $query->byStatus($status);
        }
        if ($request->boolean('favorites')) {
            $query->where('is_favorite', true);
        }

        $sort = $request->get('sort', 'title');
        match ($sort) {
            'newest' => $query->latest(),
            'rating' => $query->orderByDesc('rating'),
            'year' => $query->orderByDesc('release_year'),
            'recently_used' => $query->orderByDesc('updated_at'),
            default => $query->orderBy('title'),
        };

        $items = $query->paginate(12)->withQueryString();

        // Stats
        $stats = [
            'total' => MediaItem::forUser(Auth::id())->count(),
            'completed' => MediaItem::forUser(Auth::id())->byStatus('completed')->count(),
            'currently_using' => MediaItem::forUser(Auth::id())->byStatus('currently_using')->count(),
            'wishlist' => MediaItem::forUser(Auth::id())->byStatus('wishlist')->count(),
        ];

        return view('media.index', compact('items', 'stats', 'sort'));
    }

    public function create()
    {
        return view('media.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'creator' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', MediaItem::$types),
            'genre' => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
            'release_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 2),
            'description' => 'nullable|string',
            'platform' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:50',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:' . implode(',', MediaItem::$statuses),
            'rating' => 'nullable|integer|min:1|max:10',
            'personal_notes' => 'nullable|string',
            'is_favorite' => 'boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'cover_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('media-covers', 'public');
        }

        $data['user_id'] = Auth::id();

        if ($request->get('release_date')) {
            $data['release_year'] = date('Y', strtotime($request->get('release_date')));
        }

        $item = MediaItem::create($data);

        if ($request->boolean('ai_enrich')) {
            $metadata = $this->ai->enrichMediaMetadata($item->title, $item->creator, $item->type);
            if (!empty($metadata)) {
                $item->update(array_filter([
                    'genre' => $item->genre ?? ($metadata['genre'] ?? null),
                    'description' => $item->description ?? ($metadata['description'] ?? null),
                    'release_year' => $item->release_year ?? ($metadata['release_year'] ?? null),
                    'duration_minutes' => $item->duration_minutes ?? ($metadata['duration_minutes'] ?? null),
                    'platform' => $item->platform ?? ($metadata['platform'] ?? null),
                    'tags' => $metadata['tags'] ?? null,
                ]));
            }
        }

        return redirect()->route('media.show', $item)->with('success', 'Media item added!');
    }

    public function show(MediaItem $mediaItem)
    {
        $this->authorizeAccess($mediaItem);

        // Lazy generate AI summary
        if (!$mediaItem->ai_summary) {
            try {
                $summary = $this->ai->generateMediaSummary($mediaItem);
                $mediaItem->update(['ai_summary' => $summary]);
            } catch (\Exception $e) {
            }
        }

        // Get AI recommendations for same type
        $recommendations = [];
        try {
            $recommendations = $this->ai->getMediaRecommendations(Auth::user(), $mediaItem->type, 4);
        } catch (\Exception $e) {
        }

        return view('media.show', compact('mediaItem', 'recommendations'));
    }

    public function edit(MediaItem $mediaItem)
    {
        $this->authorizeAccess($mediaItem);
        return view('media.edit', compact('mediaItem'));
    }

    public function update(Request $request, MediaItem $mediaItem)
    {
        $this->authorizeAccess($mediaItem);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'creator' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', MediaItem::$types),
            'genre' => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
            'release_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 2),
            'description' => 'nullable|string',
            'platform' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:50',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:' . implode(',', MediaItem::$statuses),
            'rating' => 'nullable|integer|min:1|max:10',
            'personal_notes' => 'nullable|string',
            'is_favorite' => 'boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'cover_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($mediaItem->cover_image && !str_starts_with($mediaItem->cover_image, 'http')) {
                Storage::disk('public')->delete($mediaItem->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('media-covers', 'public');
        }

        // Clear AI cache if major content changed
        if ($data['title'] !== $mediaItem->title) {
            Cache::forget("ai_media_summary_{$mediaItem->id}");
            $data['ai_summary'] = null;
        }

        $mediaItem->update($data);

        return redirect()->route('media.show', $mediaItem)->with('success', 'Updated successfully!');
    }

    public function destroy(MediaItem $mediaItem)
    {
        $this->authorizeAccess($mediaItem);
        $mediaItem->delete();
        return redirect()->route('media.index')->with('success', 'Item removed from collection.');
    }

    public function updateStatus(Request $request, MediaItem $mediaItem)
    {
        $this->authorizeAccess($mediaItem);
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', MediaItem::$statuses),
        ]);

        if ($data['status'] === 'currently_using' && !$mediaItem->started_at) {
            $data['started_at'] = now()->toDateString();
        }
        if ($data['status'] === 'completed' && !$mediaItem->completed_at) {
            $data['completed_at'] = now()->toDateString();
        }

        $mediaItem->update($data);

        return response()->json(['success' => true, 'status' => $data['status']]);
    }

    public function aiEnrich(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'creator' => 'required|string',
            'type' => 'required|string',
        ]);

        $metadata = $this->ai->enrichMediaMetadata($request->title, $request->creator, $request->type);
        return response()->json($metadata);
    }

    public function generateSummary(MediaItem $mediaItem)
    {
        $this->authorizeAccess($mediaItem);
        Cache::forget("ai_media_summary_{$mediaItem->id}");

        $summary = $this->ai->generateMediaSummary($mediaItem);
        $mediaItem->update(['ai_summary' => $summary]);

        return back()->with('success', 'AI summary generated!');
    }

    private function authorizeAccess(MediaItem $item): void
    {
        if ($item->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }
    }
}

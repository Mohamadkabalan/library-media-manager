<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function index()
    {
        return view('ai.chat');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
            'context' => 'in:library,media',
        ]);

        $messages = collect($request->messages)->map(fn($m) => [
            'role' => $m['role'],
            'content' => strip_tags($m['content']),
        ])->toArray();

        $context = $request->get('context', 'library');
        $response = $this->ai->libraryChat($messages, $context);

        if (!$response) {
            return response()->json(['error' => 'AI service temporarily unavailable.'], 503);
        }

        return response()->json(['message' => $response]);
    }

    public function recommendations(Request $request)
    {
        $user = auth()->user();
        $type = $request->get('type', 'books');

        if ($type === 'books') {
            $recs = $this->ai->getBookRecommendations($user, 6);
        } else {
            $mediaType = $request->get('media_type', 'movie');
            $recs = $this->ai->getMediaRecommendations($user, $mediaType, 6);
        }

        return response()->json(['recommendations' => $recs]);
    }
}

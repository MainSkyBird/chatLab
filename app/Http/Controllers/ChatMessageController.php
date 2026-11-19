<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ChatMessageController extends Controller
{
    public function index(Request $request, Chat $chat)
    {
        Gate::authorize('view', $chat);

        $messages = $chat->messages()
            ->select(['id', 'user_id', 'body', 'created_at'])
            ->orderBy('id')
            ->simplePaginate(perPage: 20);

        $data = [
            'data' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'has_more' => $messages->hasMorePages(),
            ]
        ];

        return response()->json($data);
    }

    public function store(Request $request, Chat $chat)
    {
        Gate::authorize('sendMessage', $chat);

        $validatedData = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $userId = Auth::user()->id;

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $userId,
            'body' => $validatedData['body'],
        ]);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'user_id' => $userId,
                'body' => $message->body,
                'created_at' => $message->created_at,
            ]
        ])->setStatusCode(201);
    }
}

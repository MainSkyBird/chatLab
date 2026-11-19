<?php

namespace App\Http\Controllers;

use App\Enums\ChatType;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return response()->json([
            'data' => $user->chats()
                ->select(['chats.id', 'chats.name', 'chats.type'])
                ->orderByDesc('chats.id')
                ->get()->makeHidden(['pivot']),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        $chat = DB::transaction(function () use ($validatedData, $user) {
            $chat = Chat::create([
                'name' => $validatedData['name'],
                'type' => ChatType::Group,
            ]);

            $user->chats()->attach($chat->id);

            return $chat;
        });

        return response()->json([
            'data' => [
                'id' => $chat->id,
                'name' => $chat->name,
                'type' => $chat->type,
            ],
        ])
            ->setStatusCode(201);
    }
}

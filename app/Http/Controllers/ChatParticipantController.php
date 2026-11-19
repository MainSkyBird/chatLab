<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChatParticipantController extends Controller
{
    public function store(Request $request, Chat $chat)
    {
        Gate::authorize('add-participant', $chat);

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id|integer',
        ]);

        try {
            DB::transaction(function () use ($chat, $validatedData) {
                $chat->users()->attach($validatedData['user_id']);
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'User is already a participant in this chat.',
            ], 409);
        }

        return response()->json([
            'data' => [
                'chat_id' => $chat->id,
                'user_id' => $validatedData['user_id'],
            ],
        ])->setStatusCode(201);
    }
}

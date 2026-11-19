<?php

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

pest()->use(RefreshDatabase::class);

test('participant can send message', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    $response = $this->actingAs($user)->post("/chats/{$chat->id}/messages", [
        'body' => 'Test message',
    ]);

    $this->assertDatabaseHas('messages', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'Test message',
    ]);

    $message = Message::where('chat_id', $chat->id)
        ->where('user_id', $user->id)
        ->where('body', 'Test message')
        ->firstOrFail();

    $response->assertStatus(201)
    ->assertExactJsonStructure([
        'data' => [
            'id', 'user_id', 'body', 'created_at',
        ]
    ])->assertJson(fn (AssertableJson $json) =>
        $json->has('data')
            ->where('data.body', 'Test message')
            ->where('data.user_id', $user->id)
            ->where('data.id', $message->id)
            ->etc()
    );
});

test('not participant cannot send message', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post("/chats/{$chat->id}/messages", [
        'body' => 'Test message',
    ]);

    $this->assertDatabaseCount('messages', 0);

    $response->assertStatus(403);
});

test('guest can not send message', function () {
    $chat = Chat::factory()->create();

    $response = $this->actingAsGuest()->postJson("/chats/{$chat->id}/messages", [
        'body' => 'Test message',
    ]);

    $this->assertDatabaseCount('messages', 0);

    $response->assertStatus(401);
});

test('cannot send empty message', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    $response = $this->actingAs($user)->postJson("/chats/{$chat->id}/messages", [
        'body' => '',
    ]);

    $this->assertDatabaseCount('messages', 0);

    $response->assertStatus(422);
});

test('cannot send longer message', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    $response = $this->actingAs($user)->postJson("/chats/{$chat->id}/messages", [
        'body' => str_repeat('a', 5001),
    ]);

    $this->assertDatabaseCount('messages', 0);

    $response->assertStatus(422);
});

test('cannot send message to the chat that does not exist', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson("/chats/999/messages", [
        'body' => 'Test message',
    ]);

    $this->assertDatabaseCount('messages', 0);

    $response->assertStatus(404);
});

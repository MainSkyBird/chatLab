<?php

use App\Enums\ChatType;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(RefreshDatabase::class);

test('make message and check it in DB', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message',
    ])->refresh();

    expect($message->body)->toBe('test message');
});

test('get chat of message', function () {
    $chat = Chat::factory()->create();

    $user = User::factory()->create();

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message',
    ]);

    $retrievedChat = $message->chat;

    expect($retrievedChat)->toBeInstanceOf(Chat::class)
        ->and($retrievedChat->id)->toBe($chat->id);
});

test('get author of the message', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message',
    ]);

    $retrievedUser = $message->user;

    expect($retrievedUser)->toBeInstanceOf(User::class)
        ->and($retrievedUser->id)->toBe($user->id);
});

test('get messages for chat', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message 1',
    ]);

    Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message 2',
    ]);

    $messages = $chat->messages()->get();

    expect($messages)->toHaveCount(2)
        ->and($messages->pluck('body'))->toContain('test message 1')
        ->and($messages->pluck('body'))->toContain('test message 2');
});

test('get messages for user', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message 1',
    ]);

    Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message 2',
    ]);

    $messages = $user->messages()->get();

    expect($messages)->toHaveCount(2)
        ->and($messages->pluck('body'))->toContain('test message 1')
        ->and($messages->pluck('body'))->toContain('test message 2');
});

test('remove messages when chat deleted', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $message = Message::create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'test message 1',
    ]);

    assertDatabaseHas('messages', [
        'id' => $message->id,
    ]);

    $chat->delete();

    assertDatabaseMissing('messages', [
        'id' => $message->id,
    ]);
});

test('make message via factory', function () {
    $message = Message::factory()->create();

    $message->refresh();

    expect($message->body)->not()->toBeNull()
        ->and($message->user_id)->not()->toBeNull()
        ->and($message->chat_id)->not()->toBeNull()
        ->and($message->user)->toBeInstanceOf(User::class)
        ->and($message->chat)->toBeInstanceOf(Chat::class);
});

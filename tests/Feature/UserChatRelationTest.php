<?php

use App\Enums\ChatType;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

pest()->use(RefreshDatabase::class);

test("add user to the chat", function () {
    $chat = Chat::create(
        [
            'name' => 'test',
            'type' => ChatType::Private,
        ]
    );

    $user = User::factory()->create();

    $user->chats()->save($chat);

    $chat->load('users');

    expect($chat->users->first()->id)->toBe($user->id);
});

test("get users of the chat", function () {
    $chat = Chat::create([
        'name' => 'test',
        'type' => ChatType::Private,
    ]);

    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $firstUser->chats()->save($chat);
    $secondUser->chats()->save($chat);

    $users = $chat->users;

    expect($users)->toHaveCount(2)
        ->and($users->pluck('id'))->toContain($firstUser->id)
        ->and($users->pluck('id'))->toContain($secondUser->id);
});

test("get chats of the user", function () {
    $chat1 = Chat::create([
        'name' => 'test',
        'type' => ChatType::Private,
    ]);

    $chat2 = Chat::create([
        'name' => 'test2',
        'type' => ChatType::Group,
    ]);

    $user = User::factory()->create();
    $user->chats()->save($chat1);
    $user->chats()->save($chat2);

    $chats = $user->chats;
    expect($chats)->toHaveCount(2)
        ->and($chats->pluck('id'))->toContain($chat1->id)
        ->and($chats->pluck('id'))->toContain($chat2->id);
});

test("cant add user to chat twice", function () {
    $chat = Chat::create(
        [
            'name' => 'test',
            'type' => ChatType::Private,
        ]
    );

    $user = User::factory()->create();

    $user->chats()->save($chat);

    $this->assertThrows(function () use ($chat, $user) {
        $user->chats()->save($chat);
    }, QueryException::class);

});

test("relations user - chat automatically removes when delete user", function () {
    $chat = Chat::create([
        'name' => 'test',
        'type' => ChatType::Private,
    ]);

    $user = User::factory()->create();

    $user->chats()->save($chat);

    assertDatabaseHas('chat_user', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);

    $user->delete();

    assertDatabaseMissing('chat_user', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);
});

test("relations user - chat automatically removes when delete chat", function () {
    $chat = Chat::create([
        'name' => 'test',
        'type' => ChatType::Private,
    ]);

    $user = User::factory()->create();

    $user->chats()->save($chat);

    assertDatabaseHas('chat_user', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);

    $chat->delete();

    assertDatabaseMissing('chat_user', [
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);
});

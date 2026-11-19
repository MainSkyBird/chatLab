<?php

use App\Enums\ChatType;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('chat type saved as string and retrieved as enum', function () {
    $chat = Chat::create([
        'name' => 'test',
        'type' => ChatType::Private,
    ])->refresh();

    expect($chat->getRawOriginal('type'))->toBe('private')
        ->and($chat->type)->toBe(ChatType::Private);

});

test('create chat via factory', function () {
    $chat = Chat::factory()->create();

    $chat->refresh();

    expect($chat->name)->not()->toBeNull()
        ->and($chat->type)->not()->toBeNull();
});

test('user can view the chat', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    expect(Gate::forUser($user)->allows('view', $chat))->toBeTrue();
});

test('user cannot view the chat', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    expect(Gate::forUser($user)->denies('view', $chat))->toBeTrue();
});

test('user cannot view the chat after leaving', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    expect(Gate::forUser($user)->allows('view', $chat))->toBeTrue();

    $chat->users()->detach($user);

    expect(Gate::forUser($user)->denies('view', $chat))->toBeTrue();
});

test('get chats of the user', function () {
    $chat1 = Chat::factory()->create();
    $chat2 = Chat::factory()->create();
    $chat3 = Chat::factory()->create();

    $user = User::factory()->create();

    $user->chats()->attach([$chat1, $chat2, $chat3]);

    $this->actingAs($user)->getJson('chats')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('get chats of the user does not include chats of other users', function () {
    $chat1 = Chat::factory()->create();
    $chat2 = Chat::factory()->create();
    $chat3 = Chat::factory()->create();
    $chat4 = Chat::factory()->create();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->chats()->attach([$chat1, $chat2, $chat3]);
    $user2->chats()->attach([$chat4]);

    $this->actingAs($user1)->getJson('chats')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonMissing(['id' => $chat4->id]);
});

test('user does not have chats', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('chats')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('guest cannot get chats', function () {
    $this->actingAsGuest()->getJson('chats')
        ->assertStatus(401);
});

test('chats ordered by id descending', function () {
    $chat1 = Chat::factory()->create();
    $chat2 = Chat::factory()->create();
    $chat3 = Chat::factory()->create();

    $user = User::factory()->create();

    $user->chats()->attach([$chat1, $chat2, $chat3]);

    $orderedChatsIds = [$chat1->id, $chat2->id, $chat3->id];
    rsort($orderedChatsIds);

    $receivedChatsIds = $this->actingAs($user)->getJson('chats')
        ->assertOk()
        ->json('data.*.id');

    expect($receivedChatsIds)->toBe($orderedChatsIds);
});

test('get chats and check response structure', function () {
    $chat1 = Chat::factory()->create();
    $chat2 = Chat::factory()->create();

    $user = User::factory()->create();
    $user->chats()->attach([$chat1, $chat2]);

    $this->actingAs($user)->getJson('chats')
        ->assertOk()
        ->assertExactJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'type',
                ],
            ],
        ]);
});

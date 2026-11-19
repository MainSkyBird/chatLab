<?php

use App\Enums\ChatType;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

pest()->use(RefreshDatabase::class);

test('User can add new participant to a group chat', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => ChatType::Group,
    ]);

    $user1->chats()->save($chat);

    $this->assertDatabaseHas('chat_user', [
        'user_id' => $user1->id,
        'chat_id' => $chat->id,
    ]);

    $this->actingAs($user1)->postJson("/chats/{$chat->id}/participants", [
        'user_id' => $user2->id,
    ])->assertStatus(201)
        ->assertExactJsonStructure([
            'data' => [
                'chat_id', 'user_id',
            ],
        ])->assertJson(fn (AssertableJson $json) => $json->has('data')
        ->where('data.chat_id', $chat->id)
        ->where('data.user_id', $user2->id)
        );

    $this->assertDatabaseHas('chat_user', [
        'user_id' => $user2->id,
        'chat_id' => $chat->id,
    ]);
});

test('Side user cannot add new participant to a group chat', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $chat = Chat::factory()->create([
        'type' => ChatType::Group,
    ]);

    $this->actingAs($user1)->postJson("/chats/{$chat->id}/participants", [
        'user_id' => $user2->id,
    ])->assertStatus(403);

});

test('Guest user cannot add new participant to a group chat', function () {
    $user1 = User::factory()->create();
    $chat = Chat::factory()->create([
        'type' => ChatType::Group,
    ]);

    $this->postJson("/chats/{$chat->id}/participants", [
        'user_id' => $user1->id,
    ])->assertStatus(401);
});

test('User cannot add new participant in an unreal chat', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1)->postJson('/chats/999/participants', [
        'user_id' => $user2->id,
    ])->assertStatus(404);
});

test('User cannot add unreal participant to a group chat', function () {
    $user1 = User::factory()->create();
    $chat = Chat::factory()->create([
        'type' => ChatType::Group,
    ]);

    $user1->chats()->save($chat);

    $this->actingAs($user1)->postJson("/chats/{$chat->id}/participants", [
        'user_id' => 999,
    ])->assertStatus(422);
});

test('user cannot add a participant to a group chat if participant already exists', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => ChatType::Group,
    ]);

    $user1->chats()->save($chat);
    $user2->chats()->save($chat);

    $this->actingAs($user1)->postJson("/chats/{$chat->id}/participants", [
        'user_id' => $user2->id,
    ])->assertConflict();

    $count = DB::table('chat_user')
        ->where('user_id', $user2->id)
        ->where('chat_id', $chat->id)->count();

    expect($count)->toBe(1);
});

test('user cannot add a participant to a private chat', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => ChatType::Private,
    ]);

    $user1->chats()->save($chat);

    $this->actingAs($user1)->postJson("/chats/{$chat->id}/participants", [
        'user_id' => $user2->id,
    ])->assertStatus(403);
});

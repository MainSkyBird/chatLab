<?php

use App\Enums\ChatType;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

pest()->use(RefreshDatabase::class);

test('user gets chat participants', function (ChatType $chatType) {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => $chatType,
    ]);

    $chat->users()->attach([$user3, $user1, $user2]);

    $this->actingAs($user1)->getJson("chats/{$chat->id}/participants")
        ->assertOk()
        ->assertExactJsonStructure([
            'data' => [
                '*' => [
                    'id', 'name',
                ],
            ],
        ])
        ->assertJson(fn (AssertableJson $json) => $json->has('data', 3)
            ->where('data.0.id', $user1->id)
            ->where('data.1.id', $user2->id)
            ->where('data.2.id', $user3->id)
        );

})->with([
    ChatType::Group,
    ChatType::Private,
]);

test('user cannot get chat participants if not a participant', function (ChatType $chatType) {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => $chatType,
    ]);

    $chat->users()->attach([$user2, $user3]);

    $this->actingAs($user1)->getJson("chats/{$chat->id}/participants")
        ->assertStatus(403);

})->with([
    ChatType::Group,
    ChatType::Private,
]);

test('guest user cannot get chat participants', function (ChatType $chatType) {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => $chatType,
    ]);

    $chat->users()->attach([$user1, $user2, $user3]);

    $this->getJson("chats/{$chat->id}/participants")
        ->assertStatus(401);

})->with([
    ChatType::Group,
    ChatType::Private,
]);

test('user cannot get chat participants if chat does not exist', function (ChatType $chatType) {
    $user1 = User::factory()->create();

    $this->actingAs($user1)->getJson('chats/999/participants')
        ->assertStatus(404);

})->with([
    ChatType::Group,
    ChatType::Private,
]);

test('user receives participants in asc order of id', function (ChatType $chatType) {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $chat = Chat::factory()->create([
        'type' => $chatType,
    ]);

    $chat->users()->attach([$user3, $user1, $user2]);

    $participantsIdsFromModel = $chat->users()->pluck('id')->toArray();
    sort($participantsIdsFromModel);

    $participantsIdsFromResponse = $this->actingAs($user1)->getJson("chats/{$chat->id}/participants")
        ->assertOk()
        ->json('data.*.id');

    $this->assertEquals($participantsIdsFromModel, $participantsIdsFromResponse);

})->with([
    ChatType::Group,
    ChatType::Private,
]);

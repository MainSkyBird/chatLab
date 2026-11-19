<?php

use App\Enums\ChatType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

pest()->use(RefreshDatabase::class);

test('authenticate user can store a chat', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('chats', [
        'name' => 'Test Chat',
    ])
        ->assertCreated()
        ->assertExactJsonStructure([
            'data' => [
                'id', 'name', 'type',
            ],
        ])
        ->assertJson(fn (AssertableJson $json) => $json->has('data')
            ->where('data.name', 'Test Chat')
            ->where('data.type', ChatType::Group)
            ->etc()
        );
});

test('user who creates chat automatically becomes a participant', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('chats', [
        'name' => 'Test Chat',
    ])
        ->assertCreated();

    $this->assertDatabaseHas('chat_user', [
        'user_id' => $user->id,
        'chat_id' => $response->json('data.id'),
    ]);
});

test('unauthorized user cannot store a chat', function () {
    $this->postJson('chats', [
        'name' => 'Test Chat',
    ])
        ->assertUnauthorized();

    $this->assertDatabaseCount('chats', 0);
});

test('empty chat name is not allowed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('chats', [
        'name' => '',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $this->assertDatabaseCount('chats', 0);
});

test('chat name is longer than limit is not allowed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('chats', [
        'name' => str_repeat('a', 256),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $this->assertDatabaseCount('chats', 0);
});

test('transaction rolls back when attaching participant fails', function () {
    $user = User::factory()->make();

    $this->actingAs($user)->postJson('chats', [
        'name' => 'test name',
    ])
        ->assertInternalServerError();

    $this->assertDatabaseCount('chats', 0);
    $this->assertDatabaseMissing('chat_user', []);
});

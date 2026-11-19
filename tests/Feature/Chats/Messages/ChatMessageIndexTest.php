<?php

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use function Pest\Laravel\assertDatabaseCount;

pest()->use(RefreshDatabase::class);

test('participant can watch messages', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();
    $message1 = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'Hello World',
    ]);

    $message2 = Message::factory()->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
        'body' => 'Hello World 2',
    ]);

    $chat->users()->attach($user);

    $this->actingAs($user)->getJson("chats/{$chat->id}/messages")
        ->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertExactJsonStructure([
            'data' => [
                '*' => ['id', 'user_id', 'body', 'created_at'],
            ],
            'pagination' => ['current_page', 'has_more']
        ])
    ->assertJson(fn (AssertableJson $json) =>
        $json->has('data', 2)
            ->has('data.0', fn (AssertableJson $json) =>
                $json->where('id', $message1->id)
                    ->where('user_id', $message1->user_id)
                ->where('body', $message1->body)
                ->etc()
            )
            ->has('data.1', fn (AssertableJson $json) =>
                $json->where('id', $message2->id)
                ->where('user_id', $message2->user_id)
                ->where('body', $message2->body)
                ->etc()
            )
            ->has('pagination', fn (AssertableJson $json) =>
                $json->where('current_page', 1)
                    ->where('has_more', false)
                ->etc()
            )
    );
});

test('not participant can not watch messages', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->getJson("chats/{$chat->id}/messages")
        ->assertStatus(403);
});

test('unauthenticated user can not watch messages', function () {
    $chat = Chat::factory()->create();

    $this->actingAsGuest()->getJson("chats/{$chat->id}/messages")
        ->assertUnauthorized();
});

test('chat doesn\'t exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson("chats/999/messages")
        ->assertNotFound();
});

test("chat is empty", function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    $this->actingAs($user)->getJson("chats/{$chat->id}/messages")
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
        $json->has('data', 0)
            ->has('pagination', null)
        );
});

test('query parameter page is working', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();
    $chat->users()->attach($user);

    Message::factory()->count(50)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->getJson("chats/{$chat->id}/messages?page=2")
        ->assertStatus(200)
    ->assertJson(fn (AssertableJson $json) =>
        $json->has('data', 20)
        ->has('pagination', fn (AssertableJson $json) =>
        $json->where('current_page', 2)
        ->where('has_more', true))
    );
});

test('first page has 20 messages', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    Message::factory()->count(50)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->getJson("chats/{$chat->id}/messages")
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 20)
                ->has('pagination', fn (AssertableJson $json) =>
                    $json->where('current_page', 1)
                        ->where('has_more', true))
        );
});

test('second page returns next messages without duplicates', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();

    $chat->users()->attach($user);

    $messages = Message::factory()->count(50)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);

    $expectedIds = $messages
        ->pluck('id')
        ->sort()
        ->values();

    $firstPageResponse = $this->actingAs($user)
        ->getJson("/chats/{$chat->id}/messages");

    $firstPageResponse
        ->assertOk()
        ->assertJsonPath('pagination.current_page', 1)
        ->assertJsonPath('pagination.has_more', true)
        ->assertJsonCount(20, 'data');

    $secondPageResponse = $this
        ->getJson("/chats/{$chat->id}/messages?page=2");

    $secondPageResponse
        ->assertOk()
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.has_more', true)
        ->assertJsonCount(20, 'data');

    $firstPageIds = $firstPageResponse
        ->collect('data')
        ->pluck('id')
        ->all();

    $secondPageIds = $secondPageResponse
        ->collect('data')
        ->pluck('id')
        ->all();

    expect($firstPageIds)
        ->toBe($expectedIds->slice(0, 20)->values()->all())
        ->and($secondPageIds)
        ->toBe($expectedIds->slice(20, 20)->values()->all());
});

test('wrong page parameter returns empty data', function () {
    $chat = Chat::factory()->create();
    $user = User::factory()->create();
    $chat->users()->attach($user);

    Message::factory()->count(50)->create([
        'chat_id' => $chat->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->getJson("chats/{$chat->id}/messages?page=999")
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 0)
                ->has('pagination', fn (AssertableJson $json) =>
                    $json->where('current_page', 999)
                        ->where('has_more', false))
        );
});

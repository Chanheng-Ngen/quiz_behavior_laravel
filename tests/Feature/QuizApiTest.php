<?php

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('can list quizzes', function () {
    Quiz::factory(3)->create();

    $response = $this->actingAs(User::factory()->create())->getJson('/api/quizzes');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'description', 'set_time_limit', 'password', 'creator_id', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can create a quiz', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/quizzes', [
        'title' => 'Test Quiz',
        'description' => 'Test Description',
        'set_time_limit' => 60,
        'password' => 'secret123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'description', 'set_time_limit', 'password', 'creator_id', 'created_at', 'updated_at'],
        ])
        ->assertJson([
            'data' => [
                'title' => 'Test Quiz',
                'description' => 'Test Description',
                'set_time_limit' => 60,
                'password' => 'secret123',
                'creator_id' => $user->id,
            ],
        ]);

    $this->assertDatabaseHas('quizzes', [
        'title' => 'Test Quiz',
        'creator_id' => $user->id,
    ]);
});

test('can view a quiz', function () {
    $quiz = Quiz::factory()->create();

    $response = $this->actingAs(User::factory()->create())->getJson("/api/quizzes/{$quiz->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'description', 'set_time_limit', 'password', 'creator_id', 'created_at', 'updated_at'],
        ])
        ->assertJson([
            'data' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
            ],
        ]);
});

test('can update own quiz', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user, 'creator')->create();

    $response = $this->actingAs($user)->patchJson("/api/quizzes/{$quiz->id}", [
        'title' => 'Updated Quiz Title',
        'description' => 'Updated Description',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'title' => 'Updated Quiz Title',
                'description' => 'Updated Description',
            ],
        ]);

    $this->assertDatabaseHas('quizzes', [
        'id' => $quiz->id,
        'title' => 'Updated Quiz Title',
    ]);
});

test('cannot update quiz created by another user', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();
    $quiz = Quiz::factory()->for($creator, 'creator')->create();

    $response = $this->actingAs($otherUser)->patchJson("/api/quizzes/{$quiz->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertStatus(403);
});

test('can delete own quiz', function () {
    $user = User::factory()->create();
    $quiz = Quiz::factory()->for($user, 'creator')->create();

    $response = $this->actingAs($user)->deleteJson("/api/quizzes/{$quiz->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
});

test('cannot delete quiz created by another user', function () {
    $creator = User::factory()->create();
    $otherUser = User::factory()->create();
    $quiz = Quiz::factory()->for($creator, 'creator')->create();

    $response = $this->actingAs($otherUser)->deleteJson("/api/quizzes/{$quiz->id}");

    $response->assertStatus(403);
});

test('cannot create quiz without title', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/api/quizzes', [
        'description' => 'Missing title',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

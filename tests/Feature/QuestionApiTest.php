<?php

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('can list questions', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'General Knowledge Quiz',
        'description' => 'Description',
        'set_time_limit' => 30,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Multiple Choice']);

    Question::create([
        'content' => 'Question 1',
        'score' => 10,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    Question::create([
        'content' => 'Question 2',
        'score' => 15,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $response = $this->actingAs(User::factory()->create())->getJson('/api/questions');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'content', 'score', 'question_type'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

test('can create a question', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Science Quiz',
        'description' => 'Description',
        'set_time_limit' => 45,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'True/False']);

    $response = $this->actingAs(User::factory()->create())->postJson("/api/quizzes/{$quiz->id}/questions", [
        'content' => 'Is Laravel a PHP framework?',
        'score' => 5,
        'question_type' => $questionType->name,
        'option_answers' => [
            ['content' => 'True', 'is_correct' => true],
            ['content' => 'False', 'is_correct' => false],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'content' => 'Is Laravel a PHP framework?',
                'score' => 5,
                'question_type' => $questionType->name,
            ],
        ]);

    $this->assertDatabaseHas('questions', [
        'content' => 'Is Laravel a PHP framework?',
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $questionId = $response->json('data.id');

    $this->assertDatabaseHas('option_answers', [
        'question_id' => $questionId,
        'content' => 'True',
        'is_correct' => 1,
    ]);

    $this->assertDatabaseHas('option_answers', [
        'question_id' => $questionId,
        'content' => 'False',
        'is_correct' => 0,
    ]);
});

test('can create many questions in one request', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Programming Quiz',
        'description' => 'Description',
        'set_time_limit' => 60,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Multiple Choice']);

    $response = $this->actingAs(User::factory()->create())->postJson("/api/quizzes/{$quiz->id}/questions", [
        'questions' => [
            [
                'content' => 'What does PHP stand for?',
                'score' => 5,
                'question_type' => $questionType->name,
            ],
            [
                'content' => 'What is Laravel used for?',
                'score' => 10,
                'question_type' => $questionType->name,
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.content', 'What does PHP stand for?')
        ->assertJsonPath('data.1.content', 'What is Laravel used for?');

    $this->assertDatabaseHas('questions', [
        'content' => 'What does PHP stand for?',
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $this->assertDatabaseHas('questions', [
        'content' => 'What is Laravel used for?',
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);
});

test('can create question with option answers', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Framework Quiz',
        'description' => 'Description',
        'set_time_limit' => 30,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Multiple Choice']);

    $response = $this->actingAs(User::factory()->create())->postJson("/api/quizzes/{$quiz->id}/questions", [
        'content' => 'What is Laravel?',
        'score' => 5,
        'question_type' => $questionType->name,
        'option_answers' => [
            ['content' => 'A PHP framework', 'is_correct' => true],
            ['content' => 'A database', 'is_correct' => false],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.content', 'What is Laravel?')
        ->assertJsonCount(2, 'data.option_answers')
        ->assertJsonPath('data.option_answers.0.content', 'A PHP framework')
        ->assertJsonPath('data.option_answers.0.is_correct', true);

    $questionId = $response->json('data.id');

    $this->assertDatabaseHas('option_answers', [
        'question_id' => $questionId,
        'content' => 'A PHP framework',
        'is_correct' => 1,
    ]);

    $this->assertDatabaseHas('option_answers', [
        'question_id' => $questionId,
        'content' => 'A database',
        'is_correct' => 0,
    ]);
});

test('can view a question', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Math Quiz',
        'description' => 'Description',
        'set_time_limit' => 60,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Single Answer']);

    $question = Question::create([
        'content' => 'What is PHP?',
        'score' => 20,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $response = $this->actingAs(User::factory()->create())->getJson("/api/questions/{$question->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $question->id,
                'content' => 'What is PHP?',
            ],
        ]);
});

test('can update a question', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'History Quiz',
        'description' => 'Description',
        'set_time_limit' => 50,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Single Answer']);

    $question = Question::create([
        'content' => 'Old content',
        'score' => 12,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $response = $this->actingAs(User::factory()->create())->patchJson("/api/questions/{$question->id}", [
        'content' => 'Updated content',
        'score' => 25,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $question->id,
                'content' => 'Updated content',
                'score' => 25,
            ],
        ]);

    $this->assertDatabaseHas('questions', [
        'id' => $question->id,
        'content' => 'Updated content',
    ]);
});

test('can delete a question', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Language Quiz',
        'description' => 'Description',
        'set_time_limit' => 40,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Single Answer']);

    $question = Question::create([
        'content' => 'Question to delete',
        'score' => 8,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $response = $this->actingAs(User::factory()->create())->deleteJson("/api/questions/{$question->id}");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Question deleted successfully.',
        ]);

    $this->assertDatabaseMissing('questions', ['id' => $question->id]);
});

test('cannot create question with invalid foreign keys', function () {
    $response = $this->actingAs(User::factory()->create())->postJson('/api/quizzes/999999/questions', [
        'content' => 'Invalid question',
        'score' => 10,
        'question_type' => 'Type does not exist',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['questions.0.quiz_id', 'questions.0.question_type']);
});

test('cannot create multiple choice question with fewer than two options', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Validation Quiz',
        'description' => 'Description',
        'set_time_limit' => 25,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'Multiple Choice']);

    $response = $this->actingAs(User::factory()->create())->postJson("/api/quizzes/{$quiz->id}/questions", [
        'content' => 'Pick one option',
        'score' => 2,
        'question_type' => $questionType->name,
        'option_answers' => [
            ['content' => 'Only option', 'is_correct' => true],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['questions.0.option_answers']);
});

test('cannot create true false question with fewer than two options', function () {
    $quizCreator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Boolean Quiz',
        'description' => 'Description',
        'set_time_limit' => 25,
        'creator_id' => $quizCreator->id,
    ]);
    $questionType = QuestionType::create(['name' => 'True/False']);

    $response = $this->actingAs(User::factory()->create())->postJson("/api/quizzes/{$quiz->id}/questions", [
        'content' => 'The sky is blue.',
        'score' => 2,
        'question_type' => $questionType->name,
        'option_answers' => [
            ['content' => 'True', 'is_correct' => true],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['questions.0.option_answers']);
});

<?php

use App\Models\OptionAnswer;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('participant quiz submission returns right wrong and score', function () {
    $creator = User::factory()->create();
    $quiz = Quiz::create([
        'title' => 'Framework Quiz',
        'description' => 'A quiz about Laravel',
        'set_time_limit' => 30,
        'creator_id' => $creator->id,
    ]);

    $questionType = QuestionType::create(['name' => 'Multiple Choice']);

    $questionOne = Question::create([
        'content' => 'What is Laravel?',
        'score' => 10,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $questionTwo = Question::create([
        'content' => 'Write a short answer',
        'score' => 5,
        'quiz_id' => $quiz->id,
        'question_type_id' => $questionType->id,
    ]);

    $correctOption = OptionAnswer::create([
        'question_id' => $questionOne->id,
        'content' => 'A PHP framework',
        'is_correct' => true,
    ]);

    OptionAnswer::create([
        'question_id' => $questionOne->id,
        'content' => 'A database',
        'is_correct' => false,
    ]);

    $response = $this->postJson("/api/quizzes/{$quiz->id}/submit", [
        'participant' => [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ],
        'answers' => [
            [
                'question_id' => $questionOne->id,
                'option_answer_id' => $correctOption->id,
                'text_answer' => null,
            ],
            [
                'question_id' => $questionTwo->id,
                'option_answer_id' => null,
                'text_answer' => 'This is my written answer',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'Quiz submitted successfully.')
        ->assertJsonPath('data.participant.full_name', 'John Doe')
        ->assertJsonPath('data.participant.email', 'john.doe@example.com')
        ->assertJsonPath('data.quiz.id', $quiz->id)
        ->assertJsonPath('data.quiz.score_total', 15)
        ->assertJsonPath('data.results.0.question_id', $questionOne->id)
        ->assertJsonPath('data.results.0.is_correct', true)
        ->assertJsonPath('data.results.0.score_earned', 10)
        ->assertJsonPath('data.results.1.question_id', $questionTwo->id)
        ->assertJsonPath('data.results.1.is_correct', false)
        ->assertJsonPath('data.results.1.score_earned', 0)
        ->assertJsonPath('data.score_got', 10)
        ->assertJsonPath('data.score_total', 15)
        ->assertJsonPath('data.submitted_answers_count', 2);
});

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\QuizStatus;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'set_time_limit',
        'password',
        'status',
        'creator_id',
        'shuffle_questions',
        'show_results_immediately',
        'allow_answer_review',
        'enable_anti_cheat',
        'max_violations',
        'allow_multiple_submissions',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected $casts = [
        'status' => QuizStatus::class,
        'shuffle_questions' => 'boolean',
        'show_results_immediately' => 'boolean',
        'allow_answer_review' => 'boolean',
        'enable_anti_cheat' => 'boolean',
        'allow_multiple_submissions' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}

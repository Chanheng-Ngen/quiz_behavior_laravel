<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAnswers extends Model
{
    protected $fillable = [
        'participant_id',
        'question_id',
        'option_answer_id',
        'text_answer',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function optionAnswer(): BelongsTo
    {
        return $this->belongsTo(OptionAnswer::class);
    }
}

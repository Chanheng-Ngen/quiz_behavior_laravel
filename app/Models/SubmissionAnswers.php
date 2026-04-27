<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionAnswers extends Model
{
    protected $fillable = [
        'submission_id',
        'question_id',
        'option_answer_id',
        'text_answer',
    ];
}

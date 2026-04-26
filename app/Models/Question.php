<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'content',
        'score',
        'quiz_id',
        'question_type_id',
    ];
}

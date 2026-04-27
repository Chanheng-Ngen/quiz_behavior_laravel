<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionAnswer extends Model
{
    protected $fillable = [
        'content',
        'is_correct',
        'question_id',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];
}

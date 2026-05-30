<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'quiz_id',
    ];

    public function cheats()
    {
        return $this->hasMany(Cheat::class);
    }

    public function submissionAnswers()
    {
        return $this->hasMany(SubmissionAnswers::class);
    }
}

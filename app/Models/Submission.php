<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'quiz_id',
        'participant_id',
        'time_spent_seconds',
        'is_auto_submitted',
        'submitted_at',
    ];

    protected $casts = [
        'is_auto_submitted' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function cheats()
    {
        return $this->hasMany(Cheat::class, 'participant_id', 'participant_id')
            ->where('cheats.quiz_id', $this->quiz_id);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cheat extends Model
{
    protected $fillable = [
        'name',
        'participant_id',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}

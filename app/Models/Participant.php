<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'full_name',
        'email',
    ];

    public function cheats()
    {
        return $this->hasMany(Cheat::class);
    }
}

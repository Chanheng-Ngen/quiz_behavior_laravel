<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ['url', 'question_id'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

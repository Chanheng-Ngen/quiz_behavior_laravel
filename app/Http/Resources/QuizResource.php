<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'set_time_limit' => $this->set_time_limit,
            'creator_id' => $this->creator_id,
            'questions' => $this->whenLoaded('questions', fn () => QuestionResource::collection($this->questions)),
            // 'creator' => $this->whenLoaded('creator', fn () => [
            //     'id' => $this->creator->id,
            //     'name' => $this->creator->name,
            //     'email' => $this->creator->email,
            // ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

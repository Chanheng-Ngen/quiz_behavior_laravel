<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'content' => $this->content,
            'score' => $this->score,
            'quiz_id' => $this->quiz_id,
            'question_type' => $this->questionType?->name,
            'option_answers' => $this->whenLoaded('optionAnswers', fn () => $this->optionAnswers->map(fn ($optionAnswer) => [
                'id' => $optionAnswer->id,
                'content' => $optionAnswer->content,
                'is_correct' => $optionAnswer->is_correct,
            ])->values()),
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
        ];
    }
}

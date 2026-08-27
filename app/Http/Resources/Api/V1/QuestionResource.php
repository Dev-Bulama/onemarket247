<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'question' => $this->question,
            'is_answered' => $this->is_answered,
            'answers' => $this->whenLoaded('answers', fn () => $this->answers->map(fn ($answer) => [
                'id' => $answer->id,
                'answer' => $answer->answer,
                'answered_by' => $answer->relationLoaded('answeredBy') ? $answer->answeredBy?->name : null,
                'created_at' => $answer->created_at,
            ])->values()),
            'created_at' => $this->created_at,
        ];
    }
}

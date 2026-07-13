<?php

namespace App\Actions\Question;

use App\Models\ProductAnswer;
use App\Models\ProductQuestion;
use App\Models\User;

class AnswerQuestionAction
{
    public function handle(ProductQuestion $question, User $answeredBy, string $answer): ProductAnswer
    {
        $productAnswer = $question->answers()->create([
            'answered_by' => $answeredBy->id,
            'answer' => $answer,
        ]);

        $question->update(['is_answered' => true]);

        return $productAnswer;
    }
}

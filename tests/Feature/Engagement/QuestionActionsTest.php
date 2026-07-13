<?php

use App\Actions\Question\AnswerQuestionAction;
use App\Actions\Question\AskQuestionAction;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;

test('asking a question creates it as unanswered', function () {
    $product = Product::factory()->create();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    $question = app(AskQuestionAction::class)->handle($product, $customer, 'Does this come in blue?');

    expect($question->is_answered)->toBeFalse()
        ->and($question->question)->toBe('Does this come in blue?')
        ->and($question->product_id)->toBe($product->id);
});

test('answering a question creates the answer and flips is_answered', function () {
    $question = ProductQuestion::factory()->create(['is_answered' => false]);
    $vendorUser = User::factory()->create();

    $answer = app(AnswerQuestionAction::class)->handle($question, $vendorUser, 'Yes, in blue and red.');

    expect($answer->answer)->toBe('Yes, in blue and red.')
        ->and($answer->answered_by)->toBe($vendorUser->id)
        ->and($question->fresh()->is_answered)->toBeTrue();
});

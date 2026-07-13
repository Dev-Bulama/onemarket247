<?php

namespace App\Actions\Question;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;

class AskQuestionAction
{
    public function handle(Product $product, User $customer, string $question): ProductQuestion
    {
        return ProductQuestion::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'question' => $question,
            'is_answered' => false,
        ]);
    }
}

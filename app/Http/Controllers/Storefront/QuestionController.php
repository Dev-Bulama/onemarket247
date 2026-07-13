<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Question\AskQuestionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\QuestionRequest;
use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function store(QuestionRequest $request, Product $product, AskQuestionAction $action): RedirectResponse
    {
        Gate::authorize('create', ProductQuestion::class);

        $action->handle($product, $request->user(), $request->string('question')->value());

        return back()->with('status', 'question-submitted');
    }
}

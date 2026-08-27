<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Question\AnswerQuestionAction;
use App\Actions\Question\AskQuestionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\QuestionRequest;
use App\Http\Resources\Api\V1\QuestionResource;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $questions = $product->questions()
            ->where('is_answered', true)
            ->with(['customer', 'answers.answeredBy'])
            ->latest()
            ->paginate(20);

        return Paginated::response($questions, QuestionResource::class);
    }

    public function store(QuestionRequest $request, Product $product, AskQuestionAction $action): JsonResponse
    {
        Gate::authorize('create', ProductQuestion::class);

        $question = $action->handle($product, $request->user(), $request->string('question')->value());

        return ApiResponse::success(new QuestionResource($question), status: 201);
    }

    public function answer(Request $request, ProductQuestion $question, AnswerQuestionAction $action): JsonResponse
    {
        Gate::authorize('answer', $question);

        $data = $request->validate(['answer' => ['required', 'string', 'max:2000']]);

        $action->handle($question, $request->user(), $data['answer']);

        return ApiResponse::success(new QuestionResource($question->fresh(['customer', 'answers.answeredBy'])), status: 201);
    }
}

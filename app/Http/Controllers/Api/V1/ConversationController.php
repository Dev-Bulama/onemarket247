<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\MarkConversationReadAction;
use App\Actions\Chat\SendMessageAction;
use App\Actions\Chat\StartConversationAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ChatMessageResource;
use App\Http\Resources\Api\V1\ConversationResource;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Vendor;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Shared by both the customer app and the vendor app — like
 * QuestionController::answer(), every route here sits behind a single
 * auth:sanctum gate and ConversationPolicy decides who may see or reply to
 * a given thread, rather than splitting into separate customer/vendor
 * controllers.
 */
class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendorId = $user->actingVendorId();

        $conversations = Conversation::query()
            ->when($vendorId, fn ($query) => $query->where('vendor_id', $vendorId))
            ->when(! $vendorId, fn ($query) => $query->where('user_id', $user->id))
            ->with(['vendor.store', 'user', 'product', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($query) => $query->whereNull('read_at')->where('sender_id', '!=', $user->id)])
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->paginate(20);

        return Paginated::response($conversations, ConversationResource::class);
    }

    public function store(Request $request, StartConversationAction $action): JsonResponse
    {
        Gate::authorize('create', Conversation::class);

        $data = $request->validate([
            'vendor_id' => ['required_without:product_id', 'integer', 'exists:vendors,id'],
            'product_id' => ['required_without:vendor_id', 'integer', 'exists:products,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = isset($data['product_id']) ? Product::findOrFail($data['product_id']) : null;
        $vendor = $product ? $product->vendor : Vendor::findOrFail($data['vendor_id']);

        $conversation = $action->handle($vendor, $request->user(), $product, $data['message'] ?? null);

        return ApiResponse::success(
            new ConversationResource($conversation->load(['vendor.store', 'user', 'product', 'latestMessage'])),
            status: 201,
        );
    }

    public function show(Request $request, Conversation $conversation, MarkConversationReadAction $markRead): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $markRead->handle($conversation, $request->user());

        $messages = $conversation->messages()
            ->with('sender')
            ->oldest()
            ->paginate(50);

        return Paginated::response($messages, ChatMessageResource::class, [
            'conversation' => new ConversationResource($conversation->load(['vendor.store', 'user', 'product'])),
        ], key: 'messages');
    }

    public function sendMessage(Request $request, Conversation $conversation, SendMessageAction $action): JsonResponse
    {
        Gate::authorize('reply', $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $action->handle($conversation, $request->user(), $data['body']);

        return ApiResponse::success(new ChatMessageResource($message->fresh('sender')), status: 201);
    }
}

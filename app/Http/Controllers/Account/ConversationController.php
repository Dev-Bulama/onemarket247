<?php

namespace App\Http\Controllers\Account;

use App\Actions\Chat\MarkConversationReadAction;
use App\Actions\Chat\SendMessageAction;
use App\Actions\Chat\StartConversationAction;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = $request->user()->conversations()
            ->with(['vendor.store', 'latestMessage'])
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->paginate(20);

        return view('account.conversations.index', ['conversations' => $conversations]);
    }

    public function store(Request $request, StartConversationAction $action): RedirectResponse
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

        return redirect()->route('account.conversations.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation, MarkConversationReadAction $markRead): View
    {
        Gate::authorize('view', $conversation);

        $markRead->handle($conversation, $request->user());

        $messages = $conversation->messages()->with('sender')->oldest()->paginate(50);

        return view('account.conversations.show', [
            'conversation' => $conversation->load(['vendor.store']),
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, Conversation $conversation, SendMessageAction $action): RedirectResponse
    {
        Gate::authorize('reply', $conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $action->handle($conversation, $request->user(), $data['body']);

        return back();
    }
}

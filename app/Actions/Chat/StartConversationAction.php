<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;

class StartConversationAction
{
    public function __construct(private readonly SendMessageAction $sendMessage) {}

    public function handle(Vendor $vendor, User $user, ?Product $product = null, ?string $firstMessage = null): Conversation
    {
        $conversation = Conversation::withoutGlobalScopes()->firstOrCreate(
            ['vendor_id' => $vendor->id, 'user_id' => $user->id],
            ['product_id' => $product?->id, 'subject' => $product?->name],
        );

        if ($firstMessage !== null && trim($firstMessage) !== '') {
            $this->sendMessage->handle($conversation, $user, $firstMessage);
        }

        return $conversation;
    }
}

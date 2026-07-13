<?php

namespace App\Actions\Order;

use App\Enums\OrderNoteVisibility;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;

class AddOrderNoteAction
{
    public function handle(Order $order, string $body, OrderNoteVisibility $visibility, ?User $author = null): OrderNote
    {
        return $order->notes()->create([
            'author_id' => $author?->id,
            'visibility' => $visibility,
            'body' => $body,
        ]);
    }
}

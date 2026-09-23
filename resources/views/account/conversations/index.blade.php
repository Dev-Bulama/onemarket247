@extends('layouts.app')

@section('title', 'Chats')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900">Chats</h1>

    <div class="mt-6 divide-y divide-gray-100 bg-white shadow rounded-lg overflow-hidden">
        @forelse ($conversations as $conversation)
            @php($isUnread = $conversation->messages()->whereNull('read_at')->where('sender_id', '!=', auth()->id())->exists())
            <a href="{{ route('account.conversations.show', $conversation) }}" class="block p-4 hover:bg-gray-50 {{ $isUnread ? 'bg-orange-50/40' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $conversation->vendor?->store?->name ?? 'Vendor' }}</p>
                        @if ($conversation->subject)
                            <p class="text-xs text-gray-500">{{ $conversation->subject }}</p>
                        @endif
                        @if ($conversation->latestMessage)
                            <p class="mt-1 text-sm text-gray-600 truncate">{{ $conversation->latestMessage->body }}</p>
                        @endif
                    </div>
                    <div class="text-right flex-none">
                        @if ($conversation->last_message_at)
                            <p class="text-xs text-gray-400">{{ $conversation->last_message_at->diffForHumans() }}</p>
                        @endif
                        @if ($conversation->isClosed())
                            <span class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500">Closed</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <p class="p-6 text-sm text-gray-500">You have no chats yet. Start one from a product or store page.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $conversations->links() }}
    </div>
@endsection

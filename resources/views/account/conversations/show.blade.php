@extends('layouts.app')

@section('title', 'Chat with '.($conversation->vendor?->store?->name ?? 'Vendor'))

@section('content')
    <a href="{{ route('account.conversations.index') }}" class="text-sm text-gray-500 hover:text-brand-orange">&larr; Back to chats</a>

    <div class="mt-3 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">{{ $conversation->vendor?->store?->name ?? 'Vendor' }}</h1>
        @if ($conversation->vendor?->store?->slug)
            <a href="{{ route('stores.show', $conversation->vendor->store->slug) }}" class="text-sm text-brand-orange hover:underline">View store</a>
        @endif
    </div>
    @if ($conversation->subject)
        <p class="text-xs text-gray-500">About: {{ $conversation->subject }}</p>
    @endif

    <div class="mt-6 bg-white shadow rounded-lg p-4 space-y-4">
        {{ $messages->links() }}

        @forelse ($messages as $message)
            @php($isMine = $message->sender_id === auth()->id())
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-md rounded-lg px-4 py-2 {{ $isMine ? 'bg-brand-orange text-white' : 'bg-gray-100 text-gray-900' }}">
                    <p class="text-xs font-semibold {{ $isMine ? 'text-white/80' : 'text-gray-500' }}">{{ $message->sender->name }}</p>
                    <p class="mt-1 text-sm whitespace-pre-line">{{ $message->body }}</p>
                    <p class="mt-1 text-[10px] {{ $isMine ? 'text-white/70' : 'text-gray-400' }}">{{ $message->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No messages yet. Say hello!</p>
        @endforelse
    </div>

    @if ($conversation->isClosed())
        <p class="mt-4 text-sm text-gray-500">This conversation has been closed.</p>
    @else
        <form method="POST" action="{{ route('account.conversations.reply', $conversation) }}" class="mt-4 flex gap-2">
            @csrf
            <textarea name="body" rows="2" required maxlength="2000" placeholder="Type a message…" class="flex-1 rounded-md border-gray-300 border px-3 py-2 text-sm"></textarea>
            <button type="submit" class="self-end rounded-md bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange">Send</button>
        </form>
        @error('body')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
@endsection

@extends('layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-900">Messages</h1>
        @if ($notifications->contains(fn ($n) => $n->read_at === null))
            <form method="POST" action="{{ route('account.notifications.read-all') }}">
                @csrf
                <button type="submit" class="text-sm text-brand-orange hover:underline">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="mt-6 divide-y divide-gray-100 bg-white shadow rounded-lg overflow-hidden">
        @forelse ($notifications as $notification)
            <div class="p-4 {{ $notification->read_at ? '' : 'bg-orange-50/40' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-900">{{ $notification->data['subject'] ?? 'Notification' }}</p>
                        @if (! empty($notification->data['body']))
                            <p class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $notification->data['body'] }}</p>
                        @endif
                        <p class="mt-2 text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="text-xs text-brand-orange hover:underline whitespace-nowrap">Mark read</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-6 text-sm text-gray-500">You have no messages yet.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
@endsection

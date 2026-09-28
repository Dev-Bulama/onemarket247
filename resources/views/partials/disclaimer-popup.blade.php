@php
    $disclaimerToShow = $pageDisclaimer ?? $activeDisclaimer ?? null;
@endphp

@if ($disclaimerToShow)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h2 class="mb-2 text-lg font-semibold text-gray-900">{{ $disclaimerToShow->title }}</h2>
            <div class="mb-6 max-h-64 overflow-y-auto whitespace-pre-line text-sm text-gray-600">{{ $disclaimerToShow->content }}</div>

            <form method="POST" action="{{ route('disclaimers.accept', $disclaimerToShow) }}">
                @csrf
                <button type="submit" class="w-full rounded-md bg-brand-orange px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-orange2">
                    {{ $disclaimerToShow->requires_acceptance ? 'Accept & Continue' : 'Got it' }}
                </button>
            </form>
        </div>
    </div>
@endif

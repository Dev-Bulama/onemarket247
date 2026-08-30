@extends('layouts.storefront')

@section('title', 'Frequently Asked Questions')

@section('content')
    @php($page = config('static_pages.faq'))
    <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ $page['title'] }}</h1>

        <dl class="mt-6 space-y-6">
            @foreach ($page['questions'] as $index => $qa)
                <div>
                    <dt class="font-semibold text-gray-900">{{ $qa['question'] }}</dt>
                    <dd class="mt-1 text-sm text-gray-700">
                        @if ($loop->index === 2)
                            Apply from the <a href="{{ route('vendor.register') }}" class="text-brand-orange hover:underline">vendor registration page</a>. Most applications are reviewed within a few business days.
                        @elseif ($loop->last)
                            Reach out through our <a href="{{ route('pages.contact') }}" class="text-brand-orange hover:underline">contact page</a> and we'll get back to you.
                        @else
                            {{ $qa['answer'] }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
@endsection

@extends('layouts.mail')

@section('title', 'Inbox')

@section('content')
<div class="bg-white rounded shadow divide-y">

    @forelse($messages as $message)
        <a
            href="{{ route('inbox.show', $message->id) }}"
            class="block p-4 hover:bg-gray-50 transition"
        >
            <div class="flex justify-between">
                <span class="font-semibold text-gray-900">
                    {{ $message->from ?? 'Unknown' }}
                </span>
                <span class="text-sm text-gray-500">
                    {{ $message->created_at->format('d M') }}
                </span>
            </div>

            <div class="text-sm font-medium text-gray-800 mt-1">
                {{ $message->subject ?? '(No subject)' }}
            </div>

            <div class="text-sm text-gray-600 truncate mt-1">
                {{ $message->snippet ?? Str::limit($message->content, 120) }}
            </div>
        </a>
    @empty
        <div class="p-6 text-center text-gray-500">
            Inbox kosong
        </div>
    @endforelse

</div>
@endsection

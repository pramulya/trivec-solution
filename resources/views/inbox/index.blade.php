@extends('layouts.mail')

@section('title', 'Inbox')

@section('content')

<div class="divide-y divide-gray-800">

@foreach ($messages as $message)
    @php
        $statusColor = match(true) {
            !auth()->user()->ai_enabled => 'bg-gray-500',
            $message->phishing_label === 'safe' => 'bg-green-500',
            $message->phishing_label === 'suspicious' => 'bg-yellow-400',
            $message->phishing_label === 'phishing' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    @endphp

    <a href="{{ route('inbox.show', $message->id) }}"
       class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition">

        {{-- INDICATOR --}}
        <span
            class="w-3 h-3 rounded-full {{ $statusColor }}"
            title="{{ $message->phishing_label
                ? strtoupper($message->phishing_label)
        : 'Not analyzed' }}"
        ></span>


        {{-- EMAIL INFO --}}
        <div class="flex-1 min-w-0">
            <div class="text-sm text-gray-300 truncate">
                {{ $message->from }}
            </div>
            <div class="text-sm text-gray-400 truncate">
                {{ $message->subject }}
            </div>
        </div>

        {{-- DATE --}}
        <div class="text-xs text-gray-500 whitespace-nowrap">
            {{ $message->created_at->format('d M') }}
        </div>
    </a>
@endforeach

</div>

<div class="flex justify-center py-6 bg-gray-900">
    {{ $messages->links() }}
</div>

@endsection

@extends('layouts.mail')

@section('title', $message->subject ?? '(No subject)')

@section('content')
<div class="bg-white rounded shadow p-6 space-y-4">

    {{-- Header --}}
    <div class="border-b pb-4">
        <h1 class="text-xl font-semibold">
            {{ $message->subject ?? '(No subject)' }}
        </h1>

        <div class="text-sm text-gray-600 mt-1">
            <span class="font-medium">{{ $message->from ?? 'Unknown sender' }}</span>
            • {{ $message->created_at->format('d M Y H:i') }}
        </div>
    </div>

    {{-- Warning Badge (placeholder AI) --}}
    @if($message->risk_level === 'high')
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded">
            ⚠️ <strong>High phishing risk detected</strong>
        </div>
    @elseif($message->risk_level === 'medium')
        <div class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded">
            ⚠️ <strong>Potential phishing email</strong>
        </div>
    @endif

    {{-- Email Content --}}
    <div class="prose max-w-none">
        {!! $message->body !!}
    </div>

    

</div>
@endsection
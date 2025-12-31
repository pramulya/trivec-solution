@extends('layouts.mail')

@section('title', $message->subject)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="bg-gray-800 border border-gray-700 rounded p-4">
        <h1 class="text-lg font-semibold text-gray-100">
            {{ $message->subject }}
        </h1>
        <p class="text-sm text-gray-400 mt-1">
            {{ $message->from }} • {{ $message->created_at->format('d M Y H:i') }}
        </p>
    </div>

    {{-- AI PHISHING PANEL --}}
    @if(auth()->user()->ai_enabled && $message->is_analyzed)
        <div class="bg-gray-900 border border-gray-700 rounded p-4 space-y-3">

            @php
                $badge = match($message->phishing_label) {
                    'phishing'   => 'bg-red-600',
                    'suspicious' => 'bg-yellow-500 text-black',
                    'safe'       => 'bg-green-600',
                    default      => 'bg-gray-600',
                };

                $rules = json_decode($message->phishing_rules, true) ?? [];
            @endphp

            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded text-sm font-semibold {{ $badge }}">
                    {{ strtoupper($message->phishing_label ?? 'UNKNOWN') }}
                </span>

                <span class="text-sm text-gray-400">
                    Risk Score: {{ $message->phishing_score ?? 0 }}/100
                </span>
            </div>

            {{-- RULES --}}
            @if(count($rules))
                <ul class="list-disc list-inside text-sm text-gray-300">
                    @foreach($rules as $rule)
                        <li>{{ ucfirst(str_replace('_',' ', $rule)) }}</li>
                    @endforeach
                </ul>
            @endif

        </div>
    @endif

    {{-- EMAIL BODY --}}
    <div class="bg-gray-800 border border-gray-700 rounded p-6
                text-sm text-gray-200 leading-relaxed
                whitespace-pre-wrap break-words">

        {!! nl2br(
            preg_replace_callback(
                '/https?:\/\/[^\s<]+/i',
                function ($match) use ($message) {

                    // AI OFF → normal link
                    if (!auth()->user()->ai_enabled) {
                        return '<a href="'.e($match[0]).'" target="_blank"
                            class="text-blue-400 underline break-all">'
                            .e($match[0]).'</a>';
                    }

                    // AI ON
                    $danger = in_array(
                        $message->phishing_label,
                        ['phishing','suspicious']
                    );

                    $class = $danger
                        ? 'bg-red-700 text-white px-1 rounded break-all'
                        : 'text-blue-400 underline break-all hover:text-blue-300';

                    return '<a href="'.e($match[0]).'" target="_blank"
                        class="'.$class.'">'
                        .e($match[0]).'</a>';
                },
                e($message->body)
            )
        ) !!}
    </div>

</div>
@endsection

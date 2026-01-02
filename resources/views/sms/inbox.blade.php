@extends('layouts.mail')

@section('title', 'SMS Inbox')

@section('content')
<div class="divide-y divide-gray-800">

@foreach ($messages as $sms)

<a href="/sms/show"
   class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition">

    {{-- DOT INDICATOR --}}
    <span class="w-3 h-3 rounded-full bg-blue-500"></span>

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-sm font-bold text-gray-200 truncate">{{ $sms['from'] }}</span>
            
            @if(auth()->user()->ai_enabled && isset($sms['analysis']))
                @php
                    $label = $sms['analysis']['label'];
                    $score = $sms['analysis']['score'];
                    $color = match($label) {
                        'phishing' => 'bg-red-600 text-white',
                        'suspicious' => 'bg-yellow-500 text-black',
                        'safe' => 'bg-green-600 text-white',
                        default => 'bg-gray-600 text-white'
                    };
                @endphp
                <span class="px-2 py-0.5 rounded text-xs font-bold uppercase {{ $color }}">
                    {{ $label }} ({{ round($score) }}%)
                </span>
            @endif
        </div>

        <div class="text-sm text-gray-400 truncate">
            {{ $sms['text'] }}
        </div>
    </div>

    <div class="text-xs text-gray-500">
        {{ $sms['time'] }}
    </div>
</a>

@endforeach

</div>
@endsection

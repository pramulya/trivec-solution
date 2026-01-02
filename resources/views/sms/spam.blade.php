@extends('layouts.mail')

@section('title', 'SMS Spam')

@section('content')
<div class="flex flex-col h-full">

    {{-- HEADER / TOOLBAR --}}
    <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-gray-900">
        <h1 class="text-xl font-semibold text-white">SMS Spam</h1>
        
        <!-- Buttons -->
        <div class="flex items-center gap-3">
            <form action="{{ route('sms.sync.termii') }}" method="POST">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-white text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Sync Termii
                </button>
            </form>
        </div>
    </div>

    {{-- MESSAGE LIST --}}
    <div class="flex-1 overflow-y-auto divide-y divide-gray-800">
        @forelse ($messages as $sms)
        <div class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition cursor-pointer">

            {{-- DOT INDICATOR --}}
            <span class="w-3 h-3 rounded-full bg-red-500"></span>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-bold text-gray-200 truncate">{{ $sms->sender }}</span>
                    
                    @if($sms->ai_label)
                        <span class="px-2 py-0.5 rounded text-xs font-bold uppercase bg-red-600 text-white">
                            {{ $sms->ai_label }} ({{ round($sms->ai_score) }}%)
                        </span>
                    @endif
                </div>

                <div class="text-sm text-gray-400 truncate">
                    {{ $sms->body }}
                </div>
            </div>

            <div class="text-xs text-gray-500">
                {{ $sms->received_at->diffForHumans() }}
            </div>
        </div>
        @empty
            <div class="p-12 text-center text-gray-500">
                No spam messages found. That's good! 🛡️
            </div>
        @endforelse
    </div>

</div>
@endsection

@extends('layouts.mail')

@section('title', 'SMS Inbox')

@section('content')
<div class="flex flex-col h-full">

    {{-- HEADER / TOOLBAR --}}
    <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-gray-900">
        <h1 class="text-xl font-semibold text-white">SMS Inbox</h1>
        
        <!-- Buttons -->
        <div class="flex items-center gap-3">
            
            <form action="{{ route('sms.sync.termii') }}" method="POST">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-white text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Sync Termii
                </button>
            </form>

            <!-- Send Message Button (AlpineJS Toggle) -->
            <div x-data="{ openSend: false }">
                <button @click="openSend = !openSend" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-medium">
                    Send SMS 📤
                </button>

                <!-- Send Input Form -->
                <div x-show="openSend" @click.away="openSend = false" class="absolute right-6 top-16 w-80 bg-gray-800 border border-gray-700 rounded shadow-xl z-50 p-4" style="display: none;">
                    <form action="{{ route('sms.send') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">To (Phone)</label>
                            <input type="text" name="to" class="w-full bg-gray-900 border border-gray-600 rounded px-2 py-1 text-white text-sm" placeholder="62812345678" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Message</label>
                            <textarea name="message" rows="3" class="w-full bg-gray-900 border border-gray-600 rounded px-2 py-1 text-white text-sm" placeholder="Type message..." required></textarea>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white py-1 rounded text-sm">
                            Send Now
                        </button>
                    </form>
                </div>
            </div>

            <!-- Add Message Button (Simulate Receive) -->
            <div x-data="{ open: false }">
            <button @click="open = !open" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-2 rounded text-sm font-medium">
                + Simulate Receive
            </button>

            <!-- Manual Input Form -->
            <div x-show="open" @click.away="open = false" class="absolute right-6 top-16 w-80 bg-gray-800 border border-gray-700 rounded shadow-xl z-50 p-4" style="display: none;">
                <form action="{{ route('sms.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Sender (From)</label>
                        <input type="text" name="sender" class="w-full bg-gray-900 border border-gray-600 rounded px-2 py-1 text-white text-sm" placeholder="+628..." required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Message</label>
                        <textarea name="body" rows="3" class="w-full bg-gray-900 border border-gray-600 rounded px-2 py-1 text-white text-sm" placeholder="Type message..." required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-1 rounded text-sm">
                        Simulate Receive
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- MESSAGE LIST --}}
    <div class="flex-1 overflow-y-auto divide-y divide-gray-800">
        @forelse ($messages as $sms)
        <div class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition cursor-pointer">

            {{-- DOT INDICATOR --}}
            <span class="w-3 h-3 rounded-full {{ $sms->ai_label === 'phishing' ? 'bg-red-500' : 'bg-blue-500' }}"></span>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-bold text-gray-200 truncate">{{ $sms->sender }}</span>
                    
                    @if($sms->ai_label)
                        @php
                            $label = $sms->ai_label;
                            $score = $sms->ai_score;
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

                    @if($sms->source === 'manual')
                        <span class="text-xs text-gray-500 border border-gray-600 px-1 rounded">MANUAL</span>
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
                No SMS messages yet. Try adding one!
            </div>
        @endforelse
    </div>

</div>
@endsection

@extends('layouts.mail')

@section('title', 'SMS Sent')

@section('content')
<div class="flex flex-col h-full">

    {{-- HEADER / TOOLBAR --}}
    <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-gray-900">
        <h1 class="text-xl font-semibold text-white">SMS Sent</h1>

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
    </div>

    {{-- MESSAGE LIST --}}
    <div class="flex-1 overflow-y-auto divide-y divide-gray-800">
        @forelse ($messages as $sms)
        <div class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition cursor-pointer">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-bold text-gray-200 truncate">{{ $sms->sender }}</span>
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
                <p class="mb-2">No sent SMS messages.</p>
                <p class="text-xs text-gray-600">Note: The prototype currently simulates <b>receiving</b> messages.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection

@extends('layouts.mail')

@section('title', 'Sent')

@section('content')
<div class="h-full flex flex-col">

    {{-- HEADER --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-900">
        <h1 class="text-xl font-bold text-gray-100 flex items-center gap-2">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
            Sent
        </h1>

        <div class="flex items-center gap-3">
             <form action="{{ route('gmail.sync') }}" method="POST">
                @csrf
                <input type="hidden" name="folder" value="sent">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sync Sent
                </button>
            </form>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="mx-6 mt-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mx-6 mt-4 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- MESSAGE LIST --}}
    <div class="flex-1 overflow-y-auto mt-2">
        @if($messages->count() > 0)
            <div class="divide-y divide-gray-800">
                @foreach($messages as $message)
                    <a href="{{ route('inbox.show', $message) }}" 
                    class="block hover:bg-gray-800/50 transition duration-150 group">
                        <div class="px-6 py-4 flex items-center gap-4">
                            
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-gray-400 text-sm font-bold">
                                    {{ substr($message->to ?? '?', 0, 1) }} <!-- Note: Should ideally be 'To' but we store 'From' in DB. For sent, 'From' is us. Metadata might need tweak to store 'To', but for now prototype uses existing schema -->
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-medium text-gray-200 truncate">
                                        To: {{ $message->subject }} <!-- Tricky, we don't store 'To'. We'll just show Subject prominently -->
                                    </p>
                                    <span class="text-xs text-gray-500">
                                        {{ $message->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                
                                <h3 class="text-sm font-semibold text-gray-300 truncate">
                                    {{ $message->subject }}
                                </h3>
                                
                                <p class="text-sm text-gray-500 truncate mt-0.5">
                                    {{ $message->snippet }}
                                </p>
                            </div>

                        </div>
                    </a>
                @endforeach
            </div>

            <div class="px-6 py-4 border-t border-gray-800">
                {{ $messages->links() }}
            </div>

        @else
            <div class="flex flex-col items-center justify-center h-64 text-center">
                <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mb-4 text-gray-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-300">No sent messages</h3>
                <p class="text-gray-500 mt-1 max-w-sm">
                    Click "Sync Sent" to fetch your sent items.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection

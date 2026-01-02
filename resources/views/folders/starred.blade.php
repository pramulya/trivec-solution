@extends('layouts.mail')

@section('title', 'Starred')

@section('content')
<div class="h-full flex flex-col">

    {{-- HEADER --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-900">
        <h1 class="text-xl font-bold text-gray-100 flex items-center gap-2">
            <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
            </svg>
            Starred
        </h1>

        <div class="flex items-center gap-3">
             <form action="{{ route('gmail.sync') }}" method="POST">
                @csrf
                <input type="hidden" name="folder" value="starred">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sync Starred
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

    {{-- MESSAGE LIST --}}
    <div class="flex-1 overflow-y-auto mt-2">
        @if($messages->count() > 0)
            <div class="divide-y divide-gray-800">
                @foreach($messages as $message)
                    <a href="{{ route('inbox.show', $message) }}" 
                    class="block hover:bg-gray-800/50 transition duration-150 group">
                        <div class="px-6 py-4 flex items-center gap-4">
                            
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-yellow-900/50 flex items-center justify-center text-yellow-500 text-sm font-bold border border-yellow-700">
                                    {{ substr($message->from ?? '?', 0, 1) }}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-medium text-gray-200 truncate">
                                        {{ $message->from }}
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-300">No starred messages</h3>
                <p class="text-gray-500 mt-1 max-w-sm">
                    Click "Sync Starred" to fetch important messages.
                </p>
            </div>
        @endif
    </div>
</div>
@endsection

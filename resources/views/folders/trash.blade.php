@extends('layouts.mail')

@section('title', 'Trash')

@section('content')
<div class="h-full flex flex-col">

    {{-- HEADER --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-900">
        <h1 class="text-xl font-bold text-gray-100 flex items-center gap-2">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Trash
        </h1>

        <div class="flex items-center gap-3">
             <form action="{{ route('gmail.sync') }}" method="POST">
                @csrf
                <input type="hidden" name="folder" value="trash">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Sync Trash
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
                <a href="{{ route('inbox.show', $message->id) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition">
                    
                    <span class="w-3 h-3 rounded-full bg-gray-600"></span>

                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-gray-300 truncate">{{ $message->from }}</div>
                        <div class="text-sm text-gray-400 truncate">{{ $message->subject ?? '(No Subject)' }}</div>
                    </div>
                    
                    <div class="text-xs text-gray-500 whitespace-nowrap">
                        {{ $message->created_at->format('d M') }}
                    </div>
                </a>
                @endforeach
            </div>

            <div class="p-4 flex justify-center">
                {{ $messages->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <p>Trash is empty</p>
            </div>
        @endif
    </div>
</div>
@endsection

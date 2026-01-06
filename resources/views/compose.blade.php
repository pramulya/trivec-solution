@extends('layouts.mail')

@section('title', 'Compose Email')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-700">
        
        <div class="p-6 border-b border-gray-700 bg-gray-900 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">New Message</h2>
        </div>

        <form action="{{ route('compose.send') }}" method="POST" class="p-6 space-y-6" enctype="multipart/form-data">
            @csrf

            {{-- Error/Success --}}
            @if(session('error'))
                <div class="bg-red-500/10 border border-red-500 text-red-400 p-4 rounded text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">To</label>
                <input type="email" name="to" value="{{ old('to') }}" 
                       class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 transition" 
                       placeholder="recipient@example.com" required>
                @error('to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" 
                       class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 transition" 
                       placeholder="Subject line" required>
                @error('subject') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Message</label>
                <textarea name="body" rows="12" 
                          class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 transition" 
                          placeholder="Type your message here..." required>{{ old('body') }}</textarea>
                @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Attachments</label>
                <input type="file" name="attachments[]" multiple
                       class="w-full bg-gray-900 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500 transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                @error('attachments.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end pt-4">
                <a href="{{ route('inbox.index') }}" class="px-6 py-2 text-gray-400 hover:text-white mr-4 transition">Cancel</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-2 rounded font-medium shadow-lg transition transform hover:-translate-y-0.5">
                    Send Message ✈️
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>

            {{-- Gmail Integration Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
            
            @if(auth()->user()->google_token)
                <a href="{{ route('inbox.index') }}"
                class="inline-flex items-center mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    📥 Open Inbox
                </a>
            @endif

                    {{-- Title --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            Gmail Integration
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Connect your Gmail account to sync inbox and enable AI phishing analysis.
                        </p>
                    </div>

                    {{-- Status --}}
                    <div>
                        @if(auth()->user()->google_token)
                            <p class="text-sm text-green-600 font-medium">
                                ✅ Gmail connected
                            </p>
                        @else
                            <p class="text-sm text-red-600 font-medium">
                                ❌ Gmail not connected
                            </p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-3">

                        @if(auth()->user()->google_token)

                            {{-- Sync Inbox Button --}}
                            <form method="POST" action="{{ route('gmail.sync') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 transition"
                                >
                                    🔄 Sync Inbox
                                </button>
                            </form>

                            {{-- Disconnect Button --}}
                            <form method="POST" action="{{ route('google.disconnect') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-white hover:bg-gray-700 transition"
                                    onclick="return confirm('Disconnect Gmail account?')"
                                >
                                    🔌 Disconnect Gmail
                                </button>
                            </form>

                        @else
                            {{-- Connect Button --}}
                            <a
                                href="{{ route('google.redirect') }}"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 transition"
                            >
                                🔗 Connect Gmail
                            </a>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

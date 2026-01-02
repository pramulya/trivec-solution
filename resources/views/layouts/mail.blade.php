<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trivec Mail</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-900 text-gray-200">
<div class="flex min-h-screen">

    {{-- SIDEBAR --}}
    <aside class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col">

        {{-- LOGO --}}
        <div class="p-4 text-lg font-semibold tracking-wide border-b border-gray-700">
            Trivec Mail
        </div>

        {{-- FOLDERS --}}
        <nav class="mt-3 text-sm flex-1 overflow-y-auto">

            <div class="px-3 mb-4">
                <a href="{{ route('compose.index') }}" class="block w-full text-center bg-blue-600 hover:bg-blue-500 text-white py-2.5 rounded shadow-lg font-medium transition">
                    + Compose
                </a>
            </div>

            {{-- MAILBOXES --}}
            <div class="px-3 text-xs uppercase text-gray-500 mb-2">
                Mailboxes
            </div>

            <a href="/inbox"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition
            {{ request()->is('inbox*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📥 Inbox
            </a>

            <a href="/drafts"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition text-gray-300">
                📝 Drafts
            </a>

            <a href="/sent"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition
            {{ request()->is('sent*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📤 Sent
            </a>

            <a href="/starred"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition
            {{ request()->is('starred*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                ⭐ Starred
            </a>

            {{-- SMS SECTION --}}
            <div class="px-3 mt-5 text-xs uppercase text-gray-500 mb-2">
                SMS
            </div>

            <a href="/sms/inbox"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition
            {{ request()->is('sms/inbox') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📩 SMS Inbox
            </a>

            <a href="/sms/sent"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition
            {{ request()->is('sms/sent') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📤 SMS Sent
            </a>

            <a href="/sms/spam"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition
            {{ request()->is('sms/spam') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                🚨 SMS Spam
            </a>

            {{-- SECURITY --}}
            <div class="px-3 mt-5 text-xs uppercase text-gray-500 mb-2">
                Security
            </div>

            <a href="/spam"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition text-gray-300">
                🚫 Spam
            </a>

            <a href="/trash"
            class="flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition text-gray-300">
                🗑 Trash
            </a>

        </nav>


        {{-- FOOTER --}}
        <div class="p-3 text-xs text-gray-500 border-t border-gray-700">
            © {{ date('Y') }} Trivec
        </div>

    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col">

        {{-- TOP BAR --}}
        <header class="bg-gray-900 border-b border-gray-700 px-6 py-3 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-100 truncate">
                @yield('title')
            </h2>

            <div class="flex gap-2">
                @if(auth()->user()->google_refresh_token)
                    <form method="POST" action="{{ route('gmail.sync') }}">
                        @csrf
                        <button class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-sm">
                            🔄 Sync
                        </button>
                    </form>

                    <form method="POST" action="{{ route('google.disconnect') }}">
                        @csrf
                        <button class="px-3 py-1 bg-red-600 hover:bg-red-500 text-white rounded text-sm">
                            🔌 Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('google.redirect') }}"
                       class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white rounded text-sm">
                        🔗 Connect Gmail
                    </a>
                @endif
            </div>

            <form method="POST" action="{{ route('ai.toggle') }}">
                @csrf
                <button class="px-3 py-1 text-sm rounded
                    {{ auth()->user()->ai_enabled ? 'bg-purple-600' : 'bg-gray-600' }}">
                    🤖 AI {{ auth()->user()->ai_enabled ? 'ON' : 'OFF' }}
                </button>
            </form>

        </header>

        {{-- CONTENT --}}
        <section class="flex-1 overflow-y-auto bg-gray-900">
            @yield('content')
        </section>

    </main>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trivec Mail</title>
    @vite(['resources/css/app.css'])
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
        <nav class="mt-3 text-sm flex-1">

            {{-- PRIMARY --}}
            <div class="px-3 text-xs uppercase text-gray-500 mb-2">
                Mailboxes
            </div>

            <a href="/inbox"
               class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-700 transition
               {{ request()->is('inbox*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                <span class="flex items-center gap-2">
                    📥 Inbox
                </span>
            </a>

            <a href="/drafts"
               class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-700 transition text-gray-300">
                <span class="flex items-center gap-2">
                    📝 Drafts
                </span>
            </a>

            <a href="/sent"
               class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-700 transition
               {{ request()->is('sent*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                <span class="flex items-center gap-2">
                    📤 Sent
                </span>
            </a>

            <a href="/starred"
               class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-700 transition
               {{ request()->is('starred*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                <span class="flex items-center gap-2">
                    ⭐ Starred
                </span>
            </a>

            {{-- SECURITY --}}
            <div class="px-3 mt-4 text-xs uppercase text-gray-500 mb-2">
                Security
            </div>

            <a href="/spam"
               class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-700 transition text-gray-300">
                <span class="flex items-center gap-2">
                    🚨 Spam
                </span>
            </a>

            <a href="/trash"
               class="flex items-center justify-between px-4 py-2 rounded hover:bg-gray-700 transition text-gray-300">
                <span class="flex items-center gap-2">
                    🗑 Trash
                </span>
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
                    {{ auth()->user()->ai_mode ? 'bg-purple-600' : 'bg-gray-600' }}">
                    🤖 AI {{ auth()->user()->ai_mode ? 'ON' : 'OFF' }}
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

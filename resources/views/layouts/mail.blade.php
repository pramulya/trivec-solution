<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trivec Mail</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 h-screen overflow-hidden">

<div class="flex h-full">

    {{-- Sidebar --}}
    <aside class="w-64 bg-white border-r p-4">
        <h1 class="text-xl font-bold mb-6">📧 Trivec Mail</h1>

        <nav class="space-y-2">
            <a href="/inbox" class="block px-3 py-2 rounded hover:bg-gray-100">📥 Inbox</a>
            <a href="/sent" class="block px-3 py-2 rounded hover:bg-gray-100">📤 Sent</a>
            <a href="/starred" class="block px-3 py-2 rounded hover:bg-gray-100">⭐ Starred</a>
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 flex flex-col">

        {{-- Top Bar --}}
        <header class="bg-white border-b px-6 py-3 flex justify-between items-center">
            <h2 class="text-lg font-semibold">@yield('title')</h2>

            <div class="flex gap-2">
                @if(auth()->user()->google_token)
                    <form method="POST" action="{{ route('gmail.sync') }}">
                        @csrf
                        <button class="px-3 py-1 bg-blue-600 text-white rounded">
                            🔄 Sync
                        </button>
                    </form>

                    <form method="POST" action="{{ route('google.disconnect') }}">
                        @csrf
                        <button class="px-3 py-1 bg-gray-600 text-white rounded">
                            🔌 Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('google.redirect') }}"
                       class="px-3 py-1 bg-red-600 text-white rounded">
                        🔗 Connect Gmail
                    </a>
                @endif
            </div>
        </header>

        {{-- Page Content --}}
        <section class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </section>

    </main>
</div>

</body>
</html>

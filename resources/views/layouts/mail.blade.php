<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trivec Mail</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Preloading Critical Routes --}}
    <link rel="prefetch" href="{{ route('inbox.index') }}">
    <link rel="prefetch" href="{{ route('sent') }}">
    <link rel="prefetch" href="{{ route('trash.index') }}">
    <link rel="prefetch" href="{{ route('compose.index') }}">
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



            <a href="/inbox" data-folder="inbox" class="nav-link flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition {{ request()->is('inbox*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📥 Inbox
            </a>

            <a href="/drafts" data-folder="drafts" class="nav-link flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition {{ request()->is('drafts*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📝 Drafts
            </a>

            <a href="/sent" data-folder="sent" class="nav-link flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition {{ request()->is('sent*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                📤 Sent
            </a>

            <a href="/starred" data-folder="starred" class="nav-link flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition {{ request()->is('starred*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                ⭐ Starred
            </a>

            <a href="/spam" data-folder="spam" class="nav-link flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition {{ request()->is('spam*') && !request()->is('sms/spam') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                🚫 Spam
            </a>

            <a href="/trash" data-folder="trash" class="nav-link flex items-center gap-2 px-4 py-2 rounded hover:bg-gray-700 transition {{ request()->is('trash*') ? 'bg-gray-700 text-white' : 'text-gray-300' }}">
                🗑 Trash
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
<script src="//instant.page/5.2.0.js" type="module" integrity="sha384-jnZyxPjiipYXnSU0ygqeac2q7CVYMbh84GO0uHryzYjKOrqHy7arGWMA0KSs,lW" crossorigin="anonymous"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Intercept Sidebar Clicks for SPA feel
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Only intercept if we are already in the "Mail Dashboard" context (app element exists)
                const app = document.getElementById('app');
                if (app) {
                    e.preventDefault();
                    
                    const folder = link.dataset.folder;
                    const href = link.getAttribute('href');

                    // Update History
                    history.pushState({ folder: folder }, '', href);

                    // Update UI Active State manually (since no reload)
                    navLinks.forEach(l => {
                        l.classList.remove('bg-gray-700', 'text-white');
                        l.classList.add('text-gray-300');
                    });
                    link.classList.remove('text-gray-300');
                    link.classList.add('bg-gray-700', 'text-white');

                    // Signal Vue Component
                    window.dispatchEvent(new CustomEvent('trivec:folder-change', { detail: folder }));
                }
            });
        });


        // Existing Sync Logic
        const path = window.location.pathname;
        let folder = 'inbox';

        if (path.includes('/sent')) folder = 'sent';
        else if (path.includes('/drafts')) folder = 'drafts';
        else if (path.includes('/starred')) folder = 'starred';
        else if (path.includes('/trash')) folder = 'trash';
        else if (path.includes('/spam') && !path.includes('/sms/spam')) folder = 'spam';
        else if (!path.includes('/inbox') && path !== '/') return; 

        // console.log(`[Trivec] Background syncing ${folder}...`);
        
        // ... rest of sync fetch ...
    });
</script>
</body>
</html>

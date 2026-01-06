<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trivec Mail</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Preloading Critical Routes --}}
    <link rel="prefetch" href="{{ route('inbox.index') }}">
    <link rel="prefetch" href="{{ route('sent') }}">
    <link rel="prefetch" href="{{ route('trash.index') }}">
    <link rel="prefetch" href="{{ route('compose.index') }}">
</head>

<body class="bg-gray-900 text-gray-200" x-data="{ mobileMenuOpen: false }">
<div class="flex min-h-screen relative">

    {{-- MOBILE OVERLAY --}}
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-gray-900/80 z-20 md:hidden" 
         style="display: none;">
    </div>

    {{-- SIDEBAR --}}
    <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 border-r border-gray-700 flex flex-col transition-transform duration-300 md:translate-x-0 md:static md:min-h-screen">

        {{-- LOGO --}}
        <div class="p-4 text-lg font-semibold tracking-wide border-b border-gray-700 flex justify-between items-center">
            <span>Trivec Mail</span>
            {{-- Close Button (Mobile) --}}
            <button @click="mobileMenuOpen = false" class="md:hidden text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
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
    <main class="flex-1 flex flex-col min-w-0">

        {{-- TOP BAR --}}
        <header class="bg-gray-900 border-b border-gray-700 px-4 md:px-6 py-3 flex justify-between items-center sticky top-0 z-10">
            
            <div class="flex items-center gap-3">
                {{-- Hamburger Button --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-400 hover:text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <h2 class="text-lg font-semibold text-gray-100 truncate">
                    @yield('title')
                </h2>
            </div>

            <div class="flex gap-2">
                @if(auth()->user()->google_refresh_token)
                    <form method="POST" action="{{ route('gmail.sync') }}">
                        @csrf
                        <button class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-sm whitespace-nowrap">
                            🔄 Sync
                        </button>
                    </form>

                    <form method="POST" action="{{ route('google.disconnect') }}">
                        @csrf
                        <button class="px-3 py-1 bg-red-600 hover:bg-red-500 text-white rounded text-sm whitespace-nowrap hidden sm:inline-block">
                            🔌 Disconnect
                        </button>
                        {{-- Mobile Icon Only --}}
                        <button class="px-3 py-1 bg-red-600 hover:bg-red-500 text-white rounded text-sm sm:hidden">
                            🔌
                        </button>
                    </form>
                @else
                    <a href="{{ route('google.redirect') }}"
                       class="px-3 py-1 bg-green-600 hover:bg-green-500 text-white rounded text-sm whitespace-nowrap">
                        🔗 Connect
                    </a>
                @endif
            </div>

            <div class="ml-2 hidden sm:block">
                <form method="POST" action="{{ route('ai.toggle') }}">
                    @csrf
                    <button class="px-3 py-1 text-sm rounded whitespace-nowrap
                        {{ auth()->user()->ai_enabled ? 'bg-purple-600' : 'bg-gray-600' }}">
                        🤖 AI {{ auth()->user()->ai_enabled ? 'ON' : 'OFF' }}
                    </button>
                </form>
            </div>

        </header>

        {{-- CONTENT --}}
        <section class="flex-1 overflow-y-auto bg-gray-900 relative">
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

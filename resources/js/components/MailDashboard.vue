<template>
    <div class="flex h-full bg-gray-900 border-t border-gray-700">
        <!-- Message List / Content Area -->
        <main class="flex-1 flex flex-col min-w-0 bg-gray-900">
            <!-- Header -->
            <header class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-gray-900 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <button v-if="viewMode === 'detail'" @click="viewMode = 'list'" class="text-gray-400 hover:text-white flex items-center gap-1 text-sm font-medium">
                        ← Back
                    </button>
                    <h2 class="text-xl font-semibold text-white capitalize">
                        {{ viewMode === 'detail' ? selectedMessage?.subject : currentFolder }}
                    </h2>
                </div>
                 <div class="text-sm text-gray-500" v-if="loading">Loading...</div>
            </header>

            <!-- VIEW MODE: LIST -->
            <div v-show="viewMode === 'list'" class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto divide-y divide-gray-800">
                    <div v-if="loading && messages.length === 0" class="p-12 text-center text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-gray-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Fetching messages...
                    </div>

                    <template v-else>
                        <div 
                            v-for="message in messages" 
                            :key="message.id" 
                            class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition cursor-pointer group"
                            @click="openMessage(message)"
                        >
                            <span class="w-3 h-3 rounded-full shrink-0" :class="getStatusColor(message)"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <span class="text-sm font-bold text-gray-200 truncate pr-2">{{ message.from }}</span>
                                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ formatDate(message.created_at) }}</span>
                                </div>
                                <div class="text-sm text-gray-400 truncate">{{ message.subject || '(No Subject)' }}</div>
                                <div class="text-xs text-gray-600 truncate mt-0.5">{{ message.snippet }}</div>
                            </div>
                        </div>
                        <div v-if="messages.length === 0" class="p-12 text-center text-gray-500">No messages in {{ currentFolder }}.</div>
                    </template>
                </div>
                
                <!-- NUMBERED PAGINATION -->
                <div v-if="pagination.last_page > 1" class="p-4 border-t border-gray-800 flex justify-center gap-2">
                     <button 
                        v-for="link in pagination.links" 
                        :key="link.label"
                        @click="changePage(link.url)"
                        :disabled="!link.url || link.active"
                        v-html="link.label"
                        class="px-3 py-1 rounded text-sm transition"
                        :class="[
                            link.active ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    ></button>
                </div>
            </div>

            <!-- VIEW MODE: DETAIL -->
            <div v-if="viewMode === 'detail' && selectedMessage" class="flex-1 overflow-y-auto p-6">
                <!-- Message Detail Content -->
                <!-- Message Detail Content -->
                <div class="max-w-4xl mx-auto">
                    <!-- Header Info -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-white mb-2">{{ selectedMessage.subject }}</h1>
                        <div class="flex justify-between items-end">
                            <div>
                                <div class="text-sm text-gray-400">
                                    From: <span class="text-gray-200 font-medium">{{ selectedMessage.from }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ selectedMessage.formatted_date }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Body Container (Paper View with Iframe Isolation) -->
                    <div class="bg-white text-black">
                        <!-- IFRAME ISOLATION: Ensures native browser styling for emails -->
                        <iframe 
                            v-if="selectedMessage.is_html"
                            class="w-full border-0"
                            :srcdoc="selectedMessage.body"
                            @load="resizeIframe"
                            scrolling="no"
                        ></iframe>
                        
                        <!-- Fallback for Plain Text (Wrapped in pre for exact format) -->
                        <div v-else class="whitespace-pre-wrap font-mono text-sm leading-relaxed p-8">
                            {{ selectedMessage.body }}
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, reactive, watch } from 'vue';

const viewMode = ref('list'); // 'list' | 'detail'
const currentFolder = ref('inbox');
const messages = ref([]);
const selectedMessage = ref(null);
const loading = ref(false);

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    links: []
});

// Cache for prefetching: { 'https://.../page=2': { data: [...], ... } }
const pageCache = new Map();

// --- Logic ---

const determineFolderFromUrl = () => {
    const path = window.location.pathname;
    if (path.includes('/sent')) return 'sent';
    if (path.includes('/drafts')) return 'drafts';
    if (path.includes('/starred')) return 'starred';
    if (path.includes('/trash')) return 'trash';
    if (path.includes('/spam')) return 'spam';
    return 'inbox';
};

const fetchMessages = async (url = null) => {
    // Check Cache First
    if (url && pageCache.has(url)) {
        console.log('Using cached page:', url);
        applyData(pageCache.get(url));
        return;
    }

    loading.value = true;
    try {
        const endpoint = url || (() => {
            switch(currentFolder.value) {
                case 'inbox': return '/inbox';
                default: return `/${currentFolder.value}`;
            }
        })();

        const response = await axios.get(endpoint);
        applyData(response.data);
        
        // --- PREFETCH NEXT PAGE ---
        if (response.data.next_page_url) {
            prefetchPage(response.data.next_page_url);
        }

    } catch (e) {
        console.error("Failed to load messages", e);
    } finally {
        loading.value = false;
    }
};

const applyData = (data) => {
    messages.value = data.data;
    pagination.current_page = data.current_page;
    pagination.last_page = data.last_page;
    pagination.links = data.links; // Laravel returns nicely formatted links links array
};

const prefetchPage = async (url) => {
    if (pageCache.has(url)) return;
    
    // Tiny delay to let main thread render first
    setTimeout(async () => {
        try {
            console.log('Prefetching:', url);
            const response = await axios.get(url);
            pageCache.set(url, response.data);
        } catch (e) {
            console.warn('Prefetch failed', e);
        }
    }, 1000);
};

const changePage = (url) => {
    if (!url) return;
    fetchMessages(url);
};

const handleFolderChange = (event) => {
    currentFolder.value = event.detail;
    viewMode.value = 'list';
    pageCache.clear(); // Clear cache on folder switch
    fetchMessages();
};

const resizeIframe = (event) => {
    const iframe = event.target;
    if (iframe) {
        // Reset height to shrink if content shrank
        iframe.style.height = '100px'; 
        // Set new height based on content
        iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
    }
};

const openMessage = async (message) => {
    selectedMessage.value = message;
    viewMode.value = 'detail';
    
    // Fetch full details (body, rules, etc) if not fully present or just to be safe
    // Ideally we click item -> basic data is there -> fetch full content for Detail View
    try {
        const response = await axios.get(`/inbox/${message.id}`);
        // Ensure we merge carefully. If response.data is the message object itself or nested.
        // Based on controller it returns { message: {...}, ... }
        const fullMessage = response.data.message;
        selectedMessage.value = { ...message, ...fullMessage, formatted_date: response.data.formatted_date };
    } catch (e) {
        console.error("Failed to fetch message details", e);
    }
};

const getStatusColor = (message) => {
    if (message.phishing_label === 'safe') return 'bg-green-500';
    if (message.phishing_label === 'suspicious') return 'bg-yellow-400';
    if (message.phishing_label === 'phishing') return 'bg-red-500';
    return 'bg-gray-500'; 
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
};

// --- BACKGROUND SYNC CRAWLER ---
const syncHistory = async (token = null) => {
    try {
        console.log('[Trivec] Syncing history...', token ? '(Next Page)' : '(Start)');
        
        // Use a separate endpoint or just params on the sync endpoint
        const response = await axios.post('/gmail/sync', {
            folder: currentFolder.value,
            pageToken: token
        });

        if (response.data.success) {
            const count = response.data.count;
            const nextToken = response.data.nextPageToken;

            // If we found messages, refresh the current view silently? 
            // Or just let them appear on next navigation/pagination. 
            // For now, let's just log it. Live updates might be too jumpy.
            if (count > 0) {
                console.log(`[Trivec] Synced ${count} messages.`);
                // Optional: If we are on page 1 and list is short, refresh?
                // if (pagination.current_page === 1 && messages.value.length < 50) fetchMessages();
            }

            // RECURSIVE CRAWL
            // Be careful not to infinite loop if Gmail has millions. 
            // Maybe limit to 10 pages for now or check if component is unmounted.
            if (nextToken) {
                setTimeout(() => syncHistory(nextToken), 2000); // 2s delay between pages
            } else {
                console.log('[Trivec] History sync complete.');
            }
        }
    } catch (e) {
        console.warn('[Trivec] Sync paused/failed', e);
    }
};

onMounted(() => {
    currentFolder.value = determineFolderFromUrl();
    fetchMessages();
    window.addEventListener('trivec:folder-change', handleFolderChange);
    
    window.addEventListener('popstate', () => {
        const newFolder = determineFolderFromUrl();
        if (newFolder !== currentFolder.value) {
            currentFolder.value = newFolder;
            viewMode.value = 'list';
            fetchMessages();
        }
    });

    // START CRAWLER after a short delay
    setTimeout(() => syncHistory(), 3000);
});

onUnmounted(() => {
    window.removeEventListener('trivec:folder-change', handleFolderChange);
});
</script>

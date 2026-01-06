<template>
    <div class="bg-gray-900 text-gray-200 flex flex-col h-full">
        <!-- Toolbar & Search -->
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-gray-900">
            <h1 class="text-xl font-semibold text-white">SMS Dashboard</h1>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative">
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Search..." 
                        class="pl-9 pr-4 py-1.5 bg-gray-800 border border-gray-600 rounded text-sm text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-500 w-48"
                    >
                    <svg class="w-4 h-4 text-gray-500 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Sync Termii -->
                <button 
                    @click="syncTermii" 
                    :disabled="isSyncing"
                    class="text-gray-400 hover:text-white text-sm flex items-center gap-1 disabled:opacity-50"
                >
                    <svg v-if="isSyncing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>{{ isSyncing ? 'Syncing...' : 'Sync Termii' }}</span>
                </button>

                <!-- Send SMS Button -->
                <button @click="showSendModal = true" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-medium flex items-center gap-2">
                    Send SMS 📤
                </button>

                <!-- Simulate Receive Button -->
                <button @click="showSimulateModal = true" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-2 rounded text-sm font-medium">
                    + Simulate
                </button>
            </div>
        </div>

        <!-- Folder Tabs -->
        <div class="px-6 border-b border-gray-700 bg-gray-900 flex gap-6 text-sm font-medium">
            <button 
                @click="switchFolder('inbox')"
                class="py-3 border-b-2 transition-colors"
                :class="currentFolder === 'inbox' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-200'"
            >
                Inbox
            </button>
            <button 
                @click="switchFolder('sent')"
                class="py-3 border-b-2 transition-colors"
                :class="currentFolder === 'sent' ? 'border-green-500 text-green-400' : 'border-transparent text-gray-400 hover:text-gray-200'"
            >
                Sent
            </button>
            <button 
                @click="switchFolder('spam')"
                class="py-3 border-b-2 transition-colors"
                :class="currentFolder === 'spam' ? 'border-red-500 text-red-400' : 'border-transparent text-gray-400 hover:text-gray-200'"
            >
                Spam
            </button>
        </div>

        <!-- Message List -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-800">
            <div 
                v-for="message in filteredMessages" 
                :key="message.id" 
                class="flex items-center gap-4 px-6 py-3 hover:bg-gray-800 transition cursor-pointer group"
            >
                <!-- Dot Indicator -->
                <span 
                    class="w-3 h-3 rounded-full shrink-0"
                    :class="message.ai_label === 'phishing' ? 'bg-red-500' : 'bg-blue-500'"
                ></span>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                         <!-- Sender Display Logic -->
                        <span class="text-sm font-bold text-gray-200 truncate">
                            {{ currentFolder === 'sent' ? 'To: ' : 'From: ' }}{{ message.sender }}
                        </span>
                        
                        <!-- AI Label -->
                        <span v-if="message.ai_label" 
                              class="px-2 py-0.5 rounded text-xs font-bold uppercase"
                              :class="getLabelColor(message.ai_label)"
                        >
                            {{ message.ai_label }} ({{ (message.ai_score * 100).toFixed(0) }}%)
                        </span>

                        <!-- Source Badge -->
                        <span v-if="message.source === 'manual'" class="text-xs text-gray-500 border border-gray-600 px-1 rounded">MANUAL</span>
                    </div>

                    <div class="text-sm text-gray-400 truncate pr-4">
                        {{ message.body }}
                    </div>
                </div>

                <div class="flex flex-col items-end gap-1">
                    <div class="text-xs text-gray-500 whitespace-nowrap">
                        {{ formatDate(message.received_at || message.created_at) }}
                    </div>
                    
                    <!-- Actions (Visible on Hover) -->
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                         <button v-if="currentFolder !== 'spam'" @click.stop="markAsSpam(message)" class="text-gray-400 hover:text-yellow-500" title="Mark as Spam">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </button>
                        <button @click.stop="deleteMessage(message)" class="text-gray-400 hover:text-red-400" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="loading" class="p-12 text-center text-gray-500">
                <svg class="animate-spin h-6 w-6 mx-auto text-gray-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading...
            </div>
            <div v-else-if="filteredMessages.length === 0" class="p-12 text-center text-gray-500">
                <span v-if="searchQuery">No results found for "{{ searchQuery }}"</span>
                <span v-else>No messages in {{ currentFolder }}.</span>
            </div>
        </div>

        <!-- Modals (Send & Simulate - Same as before) -->
         <!-- Send Modal -->
        <div v-if="showSendModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showSendModal = false">
            <div class="bg-gray-800 border border-gray-700 rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-white mb-4">Send Outbound SMS</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">To (Phone)</label>
                        <input v-model="sendForm.to" type="text" class="w-full bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-green-500" placeholder="62812345678">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Message</label>
                        <textarea v-model="sendForm.message" rows="4" class="w-full bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-green-500" placeholder="Type message..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showSendModal = false" class="text-gray-400 hover:text-white text-sm">Cancel</button>
                        <button @click="sendSms" :disabled="isSending" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-bold flex items-center gap-2">
                            <svg v-if="isSending" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ isSending ? 'Sending...' : 'Send Now' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Simulate Modal -->
        <div v-if="showSimulateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showSimulateModal = false">
            <div class="bg-gray-800 border border-gray-700 rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-white mb-4">Simulate Incoming SMS</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Sender (From)</label>
                        <input v-model="simulateForm.sender" type="text" class="w-full bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500" placeholder="+628...">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Message</label>
                        <textarea v-model="simulateForm.body" rows="4" class="w-full bg-gray-900 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-blue-500" placeholder="Type message..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="showSimulateModal = false" class="text-gray-400 hover:text-white text-sm">Cancel</button>
                        <button @click="simulateSms" :disabled="isSimulating" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-bold flex items-center gap-2">
                            <svg v-if="isSimulating" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ isSimulating ? 'Processing...' : 'Simulate Receive' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';

const props = defineProps({
    initialMessages: {
        type: Array,
        default: () => []
    }
});

const messages = ref(props.initialMessages);
const searchQuery = ref('');
const currentFolder = ref('inbox');
const loading = ref(false);

// Loading States
const isSyncing = ref(false);
const isSending = ref(false);
const isSimulating = ref(false);

// Modals
const showSendModal = ref(false);
const showSimulateModal = ref(false);

const sendForm = reactive({ to: '', message: '' });
const simulateForm = reactive({ sender: '', body: '' });

// --- Computed ---
const filteredMessages = computed(() => {
    if (!searchQuery.value) return messages.value;
    const query = searchQuery.value.toLowerCase();
    return messages.value.filter(msg => 
        msg.sender.toLowerCase().includes(query) || 
        msg.body.toLowerCase().includes(query)
    );
});

// --- Formatting ---
const getLabelColor = (label) => {
    switch(label) {
        case 'phishing': return 'bg-red-600 text-white';
        case 'suspicious': return 'bg-yellow-500 text-black';
        case 'safe': return 'bg-green-600 text-white';
        default: return 'bg-gray-600 text-white';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.round(diffMs / 60000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.round(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    return date.toLocaleDateString();
};

// --- Navigation ---

const switchFolder = async (folder) => {
    currentFolder.value = folder;
    loading.value = true;
    try {
        const response = await axios.get(`/sms/${folder}`);
        messages.value = response.data;
    } catch (e) {
        console.error(`Failed to load ${folder}`, e);
        // Fallback or empty
        messages.value = [];
    } finally {
        loading.value = false;
    }
};

// --- Actions ---

// Refresh uses current folder
const refreshMessages = async () => {
    // Avoid showing full loading state for background refresh
    try {
        const response = await axios.get(`/sms/${currentFolder.value}`);
        messages.value = response.data;
    } catch (e) {
        console.error("Failed to refresh messages", e);
    }
};

const syncTermii = async () => {
    isSyncing.value = true;
    try {
        await axios.post('/sms/sync-termii');
        // If we are not in inbox, maybe we should switch? Or just refresh current
        if (currentFolder.value === 'inbox') {
             await refreshMessages();
        }
    } catch (e) {
        alert('Sync failed: ' + (e.response?.data?.message || e.message));
    } finally {
        isSyncing.value = false;
    }
};

const sendSms = async () => {
    if (!sendForm.to || !sendForm.message) return alert('Please fill all fields');
    
    isSending.value = true;
    try {
        await axios.post('/sms/send', sendForm);
        sendForm.to = '';
        sendForm.message = '';
        showSendModal.value = false;
        
        // If we want to show the sending result, we might want to switch to 'sent' folder
        if (currentFolder.value === 'sent') {
            await refreshMessages();
        } else {
             // Optional: Ask user or auto-switch
             // switchFolder('sent');
        }
        alert('SMS Sent Successfully!');
    } catch (e) {
        alert('Send failed: ' + (e.response?.data?.message || e.message));
    } finally {
        isSending.value = false;
    }
};

const simulateSms = async () => {
    if (!simulateForm.sender || !simulateForm.body) return alert('Please fill all fields');
    
    isSimulating.value = true;
    try {
        await axios.post('/sms/store', simulateForm);
        simulateForm.sender = '';
        simulateForm.body = '';
        showSimulateModal.value = false;
        
        if (currentFolder.value === 'inbox') {
            await refreshMessages();
        }
    } catch (e) {
        alert('Simulation failed: ' + (e.response?.data?.message || e.message));
    } finally {
        isSimulating.value = false;
    }
};

const deleteMessage = async (message) => {
    if (!confirm('Are you sure you want to delete this message?')) return;
    messages.value = messages.value.filter(m => m.id !== message.id);
    try {
        await axios.delete(`/sms/${message.id}`);
    } catch (e) {
        console.error('Failed to delete', e);
    }
};

const markAsSpam = async (message) => {
    messages.value = messages.value.filter(m => m.id !== message.id);
    try {
        await axios.post(`/sms/${message.id}/spam`);
    } catch (e) {
        console.error('Failed to mark as spam', e);
    }
};
</script>

<template>
    <div class="p-6 bg-white border-b border-gray-200">
        You're logged in! (This is a Vue Component)
        <button @click="count++" class="ml-4 px-4 py-2 bg-blue-500 text-white rounded">
            Count is: {{ count }}
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

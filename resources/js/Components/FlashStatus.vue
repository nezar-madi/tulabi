<template>
    <v-alert
        v-if="status"
        type="success"
        variant="tonal"
        density="comfortable"
        class="mb-5"
        rounded="lg"
    >
        {{ status }}
    </v-alert>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

// The server's one-time message after an action (session "status").
const page = usePage();
const status = computed(() => page.props.flash?.status || '');
</script>

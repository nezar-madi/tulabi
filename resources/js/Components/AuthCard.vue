<template>
    <!-- rqmana's sign-in page body (Pages/Auth/Login.vue there): title, lead, status, form, hint. -->
    <div class="auth-page" :class="{ 'auth-page--login': large }">
        <div class="auth-page__hero">
            <h1>{{ title }}</h1>
            <p v-if="subtitle">{{ subtitle }}</p>
        </div>

        <v-alert v-if="status" type="success" variant="tonal" class="mb-5" rounded="lg">
            {{ status }}
        </v-alert>

        <slot />

        <div v-if="$slots.footer" class="auth-page__hint mt-6 mb-0">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import '@/Pages/Auth/authShared.css';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    // The larger title of the sign-in page itself.
    large: { type: Boolean, default: false },
    // Kept for the pages that pass it; the split layout gives every form the same width.
    wide: { type: Boolean, default: false },
});

const page = usePage();
const status = computed(() => page.props.flash?.status || '');
</script>

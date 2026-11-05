<template>
    <!-- The server's one-time message after an action («حُفظ الفصل.»...): a toast, not a banner -->
    <v-snackbar
        v-model="open"
        location="top end"
        :timeout="4500"
        rounded="lg"
        elevation="8"
        class="flash-toast"
        role="status"
        aria-live="polite"
    >
        <span class="flash-toast__body">
            <span class="flash-toast__icon" aria-hidden="true"><v-icon icon="tabler:check" size="16" /></span>
            <span>{{ message }}</span>
        </span>
        <template #actions>
            <v-btn icon variant="text" size="small" :aria-label="$t('common.close')" @click="open = false">
                <v-icon icon="tabler:x" size="18" />
            </v-btn>
        </template>
    </v-snackbar>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const open = ref(false);
const message = ref('');

// Every visit brings a new flash object, so the same words twice in a row (saving again) show again.
watch(() => page.props.flash, (flash) => {
    const status = flash?.status || '';

    if (status === '') {
        return;
    }

    message.value = status;
    open.value = false;
    nextTick(() => {
        open.value = true;
    });
}, { immediate: true });
</script>

<style scoped>
/* A dark toast with a green check: it reads on any page and leaves the content alone */
.flash-toast :deep(.v-snackbar__wrapper) {
    min-width: 280px;
    background: #1f2933;
    color: #fff;
}

.flash-toast__body {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
}

.flash-toast__icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--rq-success, #1e7a45);
    color: #fff;
}
</style>

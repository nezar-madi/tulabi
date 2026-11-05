<template>
    <!-- The server's one-time message after an action («حُفظ الفصل.»...): a toast at the top-left
         ("end" in the right-to-left layout), with a countdown bar that stops while the pointer is on it -->
    <v-snackbar
        v-model="open"
        location="top end"
        :timeout="5000"
        timer="bottom"
        timer-color="success"
        :elevation="0"
        class="flash-toast"
        role="status"
        aria-live="polite"
    >
        <div class="flash-toast__body">
            <span class="flash-toast__icon" aria-hidden="true"><v-icon icon="tabler:circle-check" size="22" /></span>
            <div class="flash-toast__text">
                <strong class="flash-toast__title">{{ $t('common.success_title') }}</strong>
                <span class="flash-toast__message">{{ message }}</span>
            </div>
        </div>
        <template #actions>
            <v-btn icon variant="text" size="small" class="flash-toast__close" :aria-label="$t('common.close')" @click="open = false">
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
/* A light card: a thin frame, a green edge on the reading side, a soft shadow */
.flash-toast :deep(.v-snackbar__wrapper) {
    min-width: 320px;
    max-width: 420px;
    overflow: hidden;
    border: 1px solid var(--rq-line);
    border-inline-start: 4px solid var(--rq-success);
    border-radius: 12px;
    background: var(--rq-surface);
    color: var(--rq-text-body);
    box-shadow: 0 16px 36px -16px rgb(15 23 42 / 0.35), 0 2px 6px rgb(15 23 42 / 0.06);
}

.flash-toast :deep(.v-snackbar__content) {
    padding: 14px 12px 16px 16px;
}

.flash-toast__body {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.flash-toast__icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--rq-success-tint);
    color: var(--rq-success);
}

.flash-toast__text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    padding-top: 1px;
}

.flash-toast__title {
    font-family: var(--rq-font-display);
    font-size: 14px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

.flash-toast__message {
    font-size: 13px;
    line-height: 1.6;
    color: var(--rq-text-muted);
}

.flash-toast__close {
    align-self: flex-start;
    margin-top: 6px;
    color: var(--rq-text-subtle);
}

/* The countdown along the bottom edge: thin, in the toast's green */
.flash-toast :deep(.v-snackbar__timer .v-progress-linear) {
    height: 3px !important;
    opacity: 0.85;
}
</style>

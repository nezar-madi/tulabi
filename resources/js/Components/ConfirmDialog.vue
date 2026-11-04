<template>
    <v-dialog :model-value="modelValue" max-width="420" :persistent="loading" @update:model-value="emit('update:modelValue', $event)">
        <v-card class="app-form-dialog" role="alertdialog" aria-labelledby="confirm-title" aria-describedby="confirm-text">
            <v-card-text class="pa-6">
                <div class="confirm__icon" :class="`confirm__icon--${tone}`" aria-hidden="true">
                    <v-icon :icon="icon" size="26" />
                </div>
                <h2 id="confirm-title" class="rq-dialog__title mb-2">{{ title }}</h2>
                <p id="confirm-text" class="rq-dialog__subtitle mb-0">{{ text }}</p>
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" :disabled="loading" @click="emit('update:modelValue', false)">
                    {{ $t('common.close') }}
                </v-btn>
                <v-btn
                    class="rq-btn"
                    variant="flat"
                    :color="tone === 'danger' ? 'error' : 'primary'"
                    :loading="loading"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
/*
 * «هل أنت متأكد؟» before an action that cannot be undone (a delete). The page opens it with v-model,
 * does the work on `confirm`, and passes `loading` while the request runs (the dialog then stays
 * open and cannot be closed or confirmed twice).
 */
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, required: true },
    text: { type: String, required: true },
    confirmLabel: { type: String, required: true },
    // 'danger' (red) for a delete, 'primary' for anything else.
    tone: { type: String, default: 'danger' },
    icon: { type: String, default: 'tabler:trash' },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'confirm']);
</script>

<style scoped>
.confirm__icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin-bottom: 14px;
    border-radius: 50%;
}

.confirm__icon--danger {
    background: var(--rq-danger-tint, rgb(220 38 38 / 0.1));
    color: var(--rq-danger, #dc2626);
}

.confirm__icon--primary {
    background: var(--rq-icon-tile);
    color: var(--rq-action-primary);
}
</style>

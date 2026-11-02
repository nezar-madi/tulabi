<template>
    <v-dialog :model-value="modelValue" max-width="480" @update:model-value="emit('update:modelValue', $event)">
        <v-card class="app-form-dialog">
            <v-card-text class="pa-6">
                <h2 class="rq-dialog__title mb-2">{{ title }}</h2>
                <p v-if="text" class="rq-dialog__subtitle mb-4">{{ text }}</p>
                <FormPlainTextarea
                    v-model="form.reason"
                    :label="$t('teach.requests.reason')"
                    :placeholder="$t('teach.requests.reason_hint')"
                    :rows="2"
                    :maxlength="500"
                    :error-messages="form.errors.reason"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="emit('update:modelValue', false)">{{ $t('common.cancel') }}</v-btn>
                <v-btn class="rq-btn" variant="flat" color="error" :loading="form.processing" @click="submit">
                    {{ confirmLabel }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';

// Reject or remove with an optional reason, posted to `url`.
const props = defineProps({
    modelValue: { type: Boolean, default: false },
    url: { type: String, default: '' },
    title: { type: String, required: true },
    text: { type: String, default: '' },
    confirmLabel: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue']);
const form = useForm({ reason: '' });

watch(() => props.modelValue, (open) => {
    if (open) {
        form.reset();
        form.clearErrors();
    }
});

function submit() {
    form.post(props.url, {
        preserveScroll: true,
        onSuccess: () => emit('update:modelValue', false),
    });
}
</script>

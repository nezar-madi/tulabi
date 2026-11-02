<template>
    <v-dialog :model-value="Boolean(link) && open" max-width="520" @update:model-value="open = $event">
        <v-card v-if="link" class="app-form-dialog">
            <v-card-text class="pa-6">
                <h2 class="rq-dialog__title mb-2">{{ title }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ text }}</p>
                <div class="reset-link mb-4" dir="ltr">{{ link.url }}</div>
                <div class="d-flex flex-wrap ga-2">
                    <WhatsAppButton :href="link.whatsapp">{{ whatsappLabel }}</WhatsAppButton>
                    <CopyButton :text="link.url" />
                </div>
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="open = false">{{ $t('common.close') }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import CopyButton from '@/Components/CopyButton.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';

// A one-time password reset link just made by the server (flash "resetLink").
const props = defineProps({
    link: { type: Object, default: null },
    title: { type: String, required: true },
    text: { type: String, required: true },
    whatsappLabel: { type: String, required: true },
});

const open = ref(true);

watch(() => props.link, (link) => {
    if (link) {
        open.value = true;
    }
});
</script>

<style scoped>
.reset-link {
    padding: 10px 12px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 13px;
    word-break: break-all;
    user-select: all;
}
</style>

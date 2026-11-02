<template>
    <v-btn
        v-if="href"
        class="rq-btn whatsapp-btn"
        :size="size"
        :href="href"
        target="_blank"
        rel="noopener noreferrer"
    >
        <v-icon icon="tabler:brand-whatsapp" start />
        <slot>{{ $t('common.whatsapp') }}</slot>
    </v-btn>
</template>

<script setup>
// A wa.me link made on the server (App\Support\WhatsApp), the message already written.
defineProps({
    href: { type: String, default: '' },
    size: { type: String, default: 'default' },
});
</script>

<style scoped>
/* WhatsApp's own green, as on its "Chat on WhatsApp" button (same as rqmana). */
.v-btn.whatsapp-btn {
    background-color: #25d366 !important;
    color: #fff !important;
}

.v-btn.whatsapp-btn:hover {
    background-color: #1fb855 !important;
}
</style>

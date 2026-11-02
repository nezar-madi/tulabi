<template>
    <span class="rq-badge" :class="badgeClass">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    status: { type: String, required: true },
    ended: { type: Boolean, default: false },
});

const { t } = useI18n();

const label = computed(() => (props.ended && props.status !== 'canceled' ? t('class.ended') : t(`class.status.${props.status}`)));

const badgeClass = computed(() => {
    if (props.status === 'canceled') {
        return 'rq-badge--danger';
    }

    if (props.ended || props.status === 'draft') {
        return '';
    }

    return props.status === 'published' ? 'rq-badge--success' : 'rq-badge--warning';
});
</script>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: 'tabler:circle-dot',
    },
    active: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    label: {
        type: Object,
        default: null,
    },
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'child'].includes(v),
    },
});

const emit = defineEmits(['click']);

const { t } = useI18n();

const labelText = computed(() => {
    if (!props.label) {
        return null;
    }

    if (props.label.textKey) {
        return t(props.label.textKey);
    }

    if (props.label.text) {
        return props.label.text;
    }

    return null;
});
const isChild = computed(() => props.variant === 'child');
const listDensity = computed(() => (isChild.value ? 'compact' : 'comfortable'));
const titleClass = computed(() => (isChild.value ? 'ms-0 text-body-2' : 'ms-0'));
</script>

<template>
    <v-list-item
        :class="{ 'sidebar-item--child': isChild }"
        :active="active"
        :disabled="disabled"
        :density="listDensity"
        color="primary"
        @click="emit('click')"
        class="px-4"
    >
        <template #prepend>
            <v-icon :icon="icon" :size="isChild ? 18 : 20" />
        </template>
        <v-list-item-title :class="titleClass">{{ title }}</v-list-item-title>
        <template v-if="labelText" #append>
            <v-chip size="x-small" :color="label?.color || 'primary'" variant="flat" rounded="sm">
                {{ labelText }}
            </v-chip>
        </template>
    </v-list-item>
</template>

<style scoped>
.sidebar-item--child {
    padding-inline-start: 12px !important;
    min-height: 36px;
}
</style>

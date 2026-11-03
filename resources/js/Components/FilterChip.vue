<template>
    <v-menu v-model="open" location="bottom end" offset="6" :close-on-content-click="!$slots.default">
        <template #activator="{ props: menuProps }">
            <button
                v-bind="menuProps"
                type="button"
                class="fchip"
                :class="{ 'fchip--on': active, 'fchip--open': open }"
                :aria-label="active ? `${label}: ${shown}` : label"
            >
                <v-icon :icon="icon" size="16" class="fchip__icon" />
                <span class="fchip__label">{{ label }}</span>
                <template v-if="active">
                    <span class="fchip__sep" aria-hidden="true" />
                    <span class="fchip__value">{{ shown }}</span>
                </template>
                <v-icon icon="tabler:chevron-down" size="14" class="fchip__caret" />
            </button>
        </template>

        <div class="fchip__panel">
            <!-- A custom body (e.g. two dates), or a list of choices -->
            <slot v-if="$slots.default" :close="() => (open = false)" />
            <ul v-else class="fchip__list" role="listbox">
                <li v-for="item in items" :key="String(item.value)">
                    <button
                        type="button"
                        role="option"
                        class="fchip__option"
                        :class="{ 'fchip__option--on': item.value === modelValue }"
                        :aria-selected="item.value === modelValue"
                        @click="emit('update:modelValue', item.value === modelValue ? null : item.value)"
                    >
                        <span>{{ item.title }}</span>
                        <v-icon v-if="item.value === modelValue" icon="tabler:check" size="16" />
                    </button>
                </li>
            </ul>
        </div>
    </v-menu>
</template>

<script setup>
import { computed, ref } from 'vue';

/*
 * One filter of a list, as a pill: its name, and the chosen value once one is set. Opens a short
 * list of choices (picking the chosen one again clears it), or the default slot for anything else.
 */
const props = defineProps({
    label: { type: String, required: true },
    icon: { type: String, required: true },
    // [{ title, value }]
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number], default: null },
    // What the pill shows when set from the slot (the list finds it in items).
    display: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const shown = computed(() => props.display || props.items.find((item) => item.value === props.modelValue)?.title || '');
const active = computed(() => shown.value !== '');
</script>

<style scoped>
.fchip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 36px;
    padding-inline: 12px 10px;
    border: 1px dashed var(--rq-line-strong);
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface);
    font-family: var(--rq-font-display);
    font-size: 13px;
    font-weight: 500;
    color: var(--rq-text-body);
    white-space: nowrap;
    cursor: pointer;
    transition: border-color var(--rq-dur-fast) var(--rq-ease), background-color var(--rq-dur-fast) var(--rq-ease);
}

.fchip:hover,
.fchip--open {
    border-color: var(--rq-text-subtle);
    background: var(--rq-surface-hover);
}

.fchip:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}

.fchip__icon,
.fchip__caret {
    color: var(--rq-text-subtle);
}

.fchip--on {
    border-style: solid;
    border-color: var(--rq-action-primary);
    background: var(--rq-icon-tile);
}

.fchip--on:hover,
.fchip--on.fchip--open {
    border-color: var(--rq-action-primary);
    background: var(--rq-icon-tile);
}

.fchip--on .fchip__icon,
.fchip--on .fchip__caret {
    color: var(--rq-action-primary);
}

.fchip__sep {
    width: 1px;
    height: 14px;
    background: var(--rq-line-strong);
}

.fchip__value {
    max-width: 160px;
    overflow: hidden;
    font-weight: 600;
    color: var(--rq-action-primary);
    text-overflow: ellipsis;
}

.fchip__panel {
    min-width: 200px;
    padding: 6px;
    border: 1px solid var(--rq-line);
    border-radius: 12px;
    background: var(--rq-surface);
    box-shadow: 0 12px 32px -12px rgb(15 23 42 / 0.3);
}

.fchip__list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.fchip__option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
    padding: 8px 10px;
    border: 0;
    border-radius: 8px;
    background: none;
    font-size: 14px;
    text-align: start;
    color: var(--rq-text-body);
    cursor: pointer;
}

.fchip__option:hover,
.fchip__option:focus-visible {
    outline: none;
    background: var(--rq-surface-hover);
}

.fchip__option--on {
    font-weight: 600;
    color: var(--rq-action-primary);
}
</style>

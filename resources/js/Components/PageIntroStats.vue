<template>
    <div
        class="page-intro-stats"
        :style="{ '--stat-count': Math.max(items.length, 1) }"
        :role="selectable ? 'tablist' : undefined"
    >
        <component
            :is="rootTag(item)"
            v-for="item in items"
            :key="item.key"
            :href="item.href || undefined"
            :type="rootTag(item) === 'button' ? 'button' : undefined"
            :role="selectable ? 'tab' : undefined"
            :aria-selected="selectable ? modelValue === item.key : undefined"
            class="page-intro-stat"
            :class="{
                'page-intro-stat--selectable': selectable || Boolean(item.href),
                'page-intro-stat--active': selectable && modelValue === item.key,
            }"
            @click="onItemClick(item)"
        >
            <span
                v-if="item.icon"
                class="page-intro-stat__icon"
                :class="toneClass(item.tone)"
            >
                <v-icon :icon="item.icon" size="20" />
            </span>
            <span class="page-intro-stat__body">
                <span class="page-intro-stat__label" :title="item.label">{{ item.label }}</span>
                <span class="page-intro-stat__figures">
                    <span class="page-intro-stat__value rq-number" :class="valueToneClass(item.tone)">{{ item.value ?? 0 }}</span>
                    <span
                        v-if="hasShare(item)"
                        class="page-intro-stat__share rq-number"
                        :class="toneClass(item.tone)"
                    >
                        {{ item.share }}%
                    </span>
                </span>
            </span>
        </component>
    </div>
</template>

<script setup>
/**
 * Stat cards (spec StatCard, compact row form): a 40px tone tile, the label over a 22px number,
 * and — with `share` — a tinted percentage badge beside the number.
 * With `selectable` the cards double as a filter: v-model holds the active item's key, shown
 * as the white card among soft-tinted ones.
 * Item: { key, label, value, icon?, share?, href?, tone? } — tone success|warning|danger|error|neutral
 * (or an accent: teal|violet|amber|rose|sky) colours
 * the tile, the number and the share badge.
 */

import { Link } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] },
    modelValue: { type: [String, Number, null], default: null },
    selectable: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const STATUS_TONES = {
    success: 'page-intro-stat__icon--success',
    warning: 'page-intro-stat__icon--warning',
    danger: 'page-intro-stat__icon--danger',
    error: 'page-intro-stat__icon--danger',
    neutral: 'page-intro-stat__icon--neutral',
    teal: 'page-intro-stat__icon--teal',
    violet: 'page-intro-stat__icon--violet',
    amber: 'page-intro-stat__icon--amber',
    rose: 'page-intro-stat__icon--rose',
    sky: 'page-intro-stat__icon--sky',
};

function rootTag(item) {
    if (item.href) {
        return Link;
    }

    return props.selectable ? 'button' : 'div';
}

function onItemClick(item) {
    if (props.selectable && !item.href) {
        emit('update:modelValue', item.key);
    }
}

function hasShare(item) {
    return item.share !== null && item.share !== undefined;
}

function toneClass(tone) {
    return STATUS_TONES[tone] ?? '';
}

function valueToneClass(tone) {
    return STATUS_TONES[tone] ? `page-intro-stat__value--${tone === 'error' ? 'danger' : tone}` : '';
}
</script>

<style scoped>
.page-intro-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--rq-space-3);
}

@media (min-width: 600px) {
    .page-intro-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--rq-space-4);
    }
}

/* Card: white, hairline, 8px — tile | label over number + share */
.page-intro-stat {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    padding: 18px 20px;
    overflow: hidden;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-card);
    background: var(--rq-surface);
    color: var(--rq-text-body);
    font: inherit;
    text-align: start;
}

a.page-intro-stat {
    text-decoration: none;
}

.page-intro-stat--selectable {
    cursor: pointer;
    transition:
        border-color var(--rq-dur) var(--rq-ease),
        background-color var(--rq-dur) var(--rq-ease),
        box-shadow var(--rq-dur) var(--rq-ease);
}

.page-intro-stat--selectable:hover {
    border-color: var(--rq-line-strong);
}

.page-intro-stat--selectable:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}

/* Filter cards: unselected ones sit on a soft tint, the selected one is the white card */
.page-intro-stat--selectable:not(.page-intro-stat--active) {
    background: var(--rq-surface-hover);
}

.page-intro-stat--active,
.page-intro-stat--active:hover {
    border-color: var(--rq-line);
    background: var(--rq-surface);
}

.page-intro-stat__icon {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: var(--rq-radius-lg);
    background: var(--rq-icon-tile);
    color: var(--rq-action-primary);
}

.page-intro-stat__icon :deep(.v-icon) {
    color: inherit;
}

.page-intro-stat__icon--success {
    background: var(--rq-success-tint);
    color: var(--rq-success);
}

.page-intro-stat__icon--warning {
    background: var(--rq-warning-tint);
    color: var(--rq-warning);
}

.page-intro-stat__icon--danger {
    background: var(--rq-danger-tint);
    color: var(--rq-danger);
}

.page-intro-stat__icon--neutral {
    background: var(--rq-surface-chrome);
    color: var(--rq-text-muted);
}

/* Accent tones (palette in tokens.css) for stats that aren't a status */
.page-intro-stat__icon--teal {
    background: var(--rq-accent-teal-tint);
    color: var(--rq-accent-teal);
}

.page-intro-stat__icon--violet {
    background: var(--rq-accent-violet-tint);
    color: var(--rq-accent-violet);
}

.page-intro-stat__icon--amber {
    background: var(--rq-accent-amber-tint);
    color: var(--rq-accent-amber);
}

.page-intro-stat__icon--rose {
    background: var(--rq-accent-rose-tint);
    color: var(--rq-accent-rose);
}

.page-intro-stat__icon--sky {
    background: var(--rq-accent-sky-tint);
    color: var(--rq-accent-sky);
}

.page-intro-stat--selectable:not(.page-intro-stat--active) .page-intro-stat__icon:not([class*='--success'], [class*='--warning'], [class*='--danger'], [class*='--teal'], [class*='--violet'], [class*='--amber'], [class*='--rose'], [class*='--sky']) {
    background: var(--rq-surface);
}

.page-intro-stat__body {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

/* One line; the full text is in the title tooltip when a narrow card cuts it */
.page-intro-stat__label {
    overflow: hidden;
    font-size: var(--rq-text-label);
    line-height: 1.5;
    white-space: nowrap;
    text-overflow: ellipsis;
    color: var(--rq-text-muted);
}

/* Number with its share badge beside it, sharing a baseline row */
.page-intro-stat__figures {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.page-intro-stat__value {
    font-size: 22px;
    font-weight: 700;
    line-height: 1.2;
    color: var(--rq-text-heading);
}

.page-intro-stat__value--success { color: var(--rq-success); }
.page-intro-stat__value--warning { color: var(--rq-warning); }
.page-intro-stat__value--danger { color: var(--rq-danger); }
.page-intro-stat__value--neutral { color: var(--rq-text-body); }
.page-intro-stat__value--teal { color: var(--rq-accent-teal); }
.page-intro-stat__value--violet { color: var(--rq-accent-violet); }
.page-intro-stat__value--amber { color: var(--rq-accent-amber); }
.page-intro-stat__value--rose { color: var(--rq-accent-rose); }
.page-intro-stat__value--sky { color: var(--rq-accent-sky); }

/* Share: a small tinted badge in the item's tone */
.page-intro-stat__share {
    flex-shrink: 0;
    padding: 0 6px;
    border-radius: var(--rq-radius-sm);
    font-size: 11px;
    font-weight: 600;
    line-height: 18px;
    background: var(--rq-icon-tile);
    color: var(--rq-action-primary);
}

.page-intro-stat__share.page-intro-stat__icon--success {
    background: var(--rq-success-tint);
    color: var(--rq-success);
}

.page-intro-stat__share.page-intro-stat__icon--warning {
    background: var(--rq-warning-tint);
    color: var(--rq-warning);
}

.page-intro-stat__share.page-intro-stat__icon--danger {
    background: var(--rq-danger-tint);
    color: var(--rq-danger);
}

.page-intro-stat__share.page-intro-stat__icon--neutral {
    background: var(--rq-surface-chrome);
    color: var(--rq-text-muted);
}
.page-intro-stat__share.page-intro-stat__icon--teal {
    background: var(--rq-accent-teal-tint);
    color: var(--rq-accent-teal);
}

.page-intro-stat__share.page-intro-stat__icon--violet {
    background: var(--rq-accent-violet-tint);
    color: var(--rq-accent-violet);
}

.page-intro-stat__share.page-intro-stat__icon--amber {
    background: var(--rq-accent-amber-tint);
    color: var(--rq-accent-amber);
}

.page-intro-stat__share.page-intro-stat__icon--rose {
    background: var(--rq-accent-rose-tint);
    color: var(--rq-accent-rose);
}

.page-intro-stat__share.page-intro-stat__icon--sky {
    background: var(--rq-accent-sky-tint);
    color: var(--rq-accent-sky);
}

@media (max-width: 599px) {
    .page-intro-stat {
        gap: 10px;
        padding: 14px;
    }

    .page-intro-stat__icon {
        width: 34px;
        height: 34px;
    }

    .page-intro-stat__value {
        font-size: 19px;
    }
}
</style>

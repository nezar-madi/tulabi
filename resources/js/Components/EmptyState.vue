<template>
    <section class="empty" :class="`empty--${tone}`">
        <div class="empty__art" aria-hidden="true">
            <span class="empty__ring empty__ring--outer" />
            <span class="empty__ring empty__ring--inner" />
            <span class="empty__tile"><v-icon :icon="icon" size="30" /></span>
        </div>

        <h2 class="empty__title">{{ title }}</h2>
        <p v-if="text" class="empty__text">{{ text }}</p>

        <div v-if="$slots.default" class="empty__actions">
            <slot />
        </div>
    </section>
</template>

<script setup>
/*
 * An empty list: an icon in soft rings, a short title, one line on what to do, and the actions
 * that do it. `tone` colors the icon (primary, violet, neutral).
 */
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    text: { type: String, default: '' },
    tone: { type: String, default: 'primary' },
});
</script>

<style scoped>
.empty {
    --empty-accent: var(--rq-action-primary);
    --empty-tint: var(--rq-icon-tile);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 48px 24px 52px;
    border: 1px dashed var(--rq-line-strong);
    border-radius: var(--rq-radius-card);
    background:
        radial-gradient(circle at 50% 0%, var(--empty-tint) 0%, transparent 60%),
        var(--rq-surface);
    text-align: center;
}

.empty--violet {
    --empty-accent: var(--rq-accent-violet);
    --empty-tint: var(--rq-accent-violet-tint);
}

.empty--neutral {
    --empty-accent: var(--rq-text-muted);
    --empty-tint: var(--rq-surface-tint);
}

.empty__art {
    position: relative;
    display: grid;
    place-items: center;
    width: 132px;
    height: 132px;
    margin-bottom: 18px;
}

.empty__ring {
    position: absolute;
    border-radius: 50%;
}

.empty__ring--outer {
    inset: 0;
    border: 1px dashed color-mix(in srgb, var(--empty-accent) 30%, transparent);
}

.empty__ring--inner {
    inset: 18px;
    background: var(--empty-tint);
}

.empty__tile {
    position: relative;
    display: grid;
    place-items: center;
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: var(--rq-surface);
    box-shadow: 0 10px 24px -12px color-mix(in srgb, var(--empty-accent) 60%, transparent);
    color: var(--empty-accent);
}

.empty__title {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: 18px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

.empty__text {
    max-width: 420px;
    margin: 8px 0 0;
    font-size: 14px;
    line-height: 1.8;
    color: var(--rq-text-muted);
    text-wrap: pretty;
}

.empty__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    margin-top: 22px;
}
</style>

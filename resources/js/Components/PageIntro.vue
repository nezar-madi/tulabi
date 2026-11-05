<template>
    <section class="page-intro">
        <div class="page-intro__row">
            <div class="page-intro__main">
                <div v-if="$slots.media" class="page-intro__media">
                    <slot name="media" />
                </div>
                <div class="page-intro__copy">
                    <h1 class="page-intro__title rq-display">
                        <slot name="title">{{ title }}</slot>
                    </h1>
                    <p v-if="description || $slots.description" class="page-intro__lead">
                        <slot name="description">{{ description }}</slot>
                    </p>
                    <p v-if="meta" class="page-intro__meta">
                        {{ meta }}
                    </p>
                </div>
            </div>

            <div v-if="$slots.actions" class="page-intro__actions">
                <slot name="actions" />
            </div>
        </div>

        <div v-if="$slots.default" class="page-intro__below">
            <slot />
        </div>
    </section>
</template>

<script setup>
/**
 * The opening of every page (spec PageHeader): title, description and meta line, actions on the
 * side (primary solid, secondary as tint buttons) and optional content below (e.g. PageIntroStats).
 * The #media slot puts a photo or tile before the title (detail pages). There is no breadcrumb:
 * the sidebar is the way around (owner's decision 2026-11-05).
 */
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    meta: { type: String, default: '' },
});
</script>

<style scoped>
.page-intro {
    margin-bottom: var(--rq-space-7);
    padding-top: 0;
    color: var(--rq-text-body);
}

.page-intro__row {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-5);
}

.page-intro__main {
    display: flex;
    align-items: center;
    gap: var(--rq-space-5);
    min-width: 0;
}

.page-intro__media {
    flex-shrink: 0;
}

.page-intro__copy {
    flex: 1 1 auto;
    min-width: 0;
}

.page-intro__title {
    margin: 0;
    font-size: var(--rq-text-h2);
    font-weight: 700;
    line-height: 1.3;
    color: var(--rq-text-heading);
}

/* Secondary actions: tint buttons (spec Button "secondary") */
.page-intro__actions :deep(.v-btn--variant-outlined) {
    border-color: transparent;
    background: var(--rq-surface-hover);
    color: var(--rq-action-primary) !important;
}

.page-intro__actions :deep(.v-btn--variant-outlined:hover) {
    background: var(--rq-surface-tint);
}

.page-intro__lead {
    margin: var(--rq-space-2) 0 0;
    max-width: 640px;
    font-size: var(--rq-text-lead);
    line-height: 1.7;
    color: var(--rq-text-muted);
    text-wrap: pretty;
}

.page-intro__meta {
    margin: var(--rq-space-2) 0 0;
    font-size: var(--rq-text-label);
    color: var(--rq-text-subtle);
}

.page-intro__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.page-intro__below {
    margin-top: var(--rq-space-7);
}

@media (min-width: 600px) {
    .page-intro__row {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
    }

    .page-intro__actions {
        flex-shrink: 0;
        justify-content: flex-end;
    }
}
</style>

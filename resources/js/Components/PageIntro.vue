<template>
    <section class="page-intro">
        <div class="page-intro__row">
            <div class="page-intro__main">
                <div v-if="$slots.media" class="page-intro__media">
                    <slot name="media" />
                </div>
                <div class="page-intro__copy">
                    <nav
                        v-if="trail.length"
                        class="page-intro__crumbs"
                        :aria-label="$t('nav.home')"
                    >
                        <template v-for="(crumb, index) in trail" :key="index">
                            <v-icon
                                v-if="index > 0"
                                icon="tabler:chevron-left"
                                size="14"
                                class="page-intro__crumb-sep"
                                aria-hidden="true"
                            />
                            <Link
                                v-if="crumb.href && index < trail.length - 1"
                                :href="crumb.href"
                                class="page-intro__crumb page-intro__crumb--link"
                            >
                                {{ crumb.title }}
                            </Link>
                            <span
                                v-else
                                class="page-intro__crumb"
                                :aria-current="index === trail.length - 1 ? 'page' : undefined"
                            >
                                {{ crumb.title }}
                            </span>
                        </template>
                    </nav>
                    <h1 class="page-intro__title rq-display" :class="{ 'page-intro__title--flush': !trail.length }">
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
 * The opening of every page (spec PageHeader): breadcrumb, title, description and meta line,
 * actions on the side (primary solid, secondary as tint buttons) and optional content below
 * (e.g. PageIntroStats). The #media slot puts a photo or tile before the title (detail pages).
 *
 * The breadcrumb is built from the sidebar navigation (useBreadcrumbs); `eyebrow` (or the title)
 * names the current page at its end. Pass `breadcrumbs` to override, or `:breadcrumbs="[]"` to hide.
 */
import { computed, toRef } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useBreadcrumbs } from '@/composables/useBreadcrumbs';

const props = defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    meta: { type: String, default: '' },
    breadcrumbs: { type: Array, default: null },
});

const currentLabel = computed(() => props.eyebrow || props.title);
const autoTrail = useBreadcrumbs(currentLabel);
const trail = computed(() => props.breadcrumbs ?? autoTrail.value);
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
    margin: var(--rq-space-2) 0 0;
    font-size: var(--rq-text-h2);
    font-weight: 700;
    line-height: 1.3;
    color: var(--rq-text-heading);
}

.page-intro__title--flush {
    margin-top: 0;
}

/* Breadcrumb: ancestors are blue links, the current page is muted; chevron-left separators */
.page-intro__crumbs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-label);
    line-height: 1.5;
}

.page-intro__crumb {
    color: var(--rq-text-muted);
}

.page-intro__crumb--link {
    color: var(--rq-text-link);
    text-decoration: none;
    transition: color var(--rq-dur) var(--rq-ease);
}

.page-intro__crumb--link:hover {
    color: var(--rq-text-link-hover);
}

.page-intro__crumb-sep {
    color: var(--rq-text-subtle);
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

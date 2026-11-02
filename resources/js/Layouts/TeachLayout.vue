<template>
    <PublicLayout>
        <template #subnav>
            <nav class="teach-nav">
                <Link
                    v-for="item in items"
                    :key="item.route"
                    :href="route(item.route)"
                    class="teach-nav__item"
                    :class="{ 'teach-nav__item--active': route().current(item.match) }"
                >
                    <v-icon :icon="item.icon" size="18" />
                    {{ $t(item.label) }}
                </Link>
                <a
                    v-if="publicUrl"
                    :href="publicUrl"
                    target="_blank"
                    rel="noopener"
                    class="teach-nav__item"
                >
                    <v-icon icon="tabler:external-link" size="18" />
                    {{ $t('teach.nav.view_page') }}
                </a>
            </nav>
        </template>

        <div class="teach-main">
            <FlashStatus />
            <v-alert
                v-if="$page.props.errors?.class"
                type="error"
                variant="tonal"
                density="comfortable"
                class="mb-5"
                rounded="lg"
            >
                {{ $page.props.errors.class }}
            </v-alert>
            <slot />
        </div>
    </PublicLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FlashStatus from '@/Components/FlashStatus.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const page = usePage();
const publicUrl = computed(() => page.props.auth?.user?.instructorUrl || null);

const items = [
    { route: 'teach.dashboard', match: 'teach.dashboard', label: 'teach.nav.dashboard', icon: 'tabler:layout-dashboard' },
    { route: 'teach.classes.index', match: 'teach.classes.*', label: 'teach.nav.classes', icon: 'tabler:chalkboard' },
    { route: 'teach.profile.edit', match: 'teach.profile.*', label: 'teach.nav.profile', icon: 'tabler:user' },
];
</script>

<style scoped>
.teach-nav {
    display: flex;
    gap: 4px;
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 12px;
    overflow-x: auto;
    scrollbar-width: none;
}

.teach-nav__item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    border-bottom: 2px solid transparent;
    color: var(--rq-text-muted);
    font-family: var(--rq-font-display);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    white-space: nowrap;
}

.teach-nav__item:hover {
    color: var(--rq-text-heading);
}

.teach-nav__item--active {
    border-bottom-color: var(--rq-line-emphasis);
    color: var(--rq-text-heading);
}

.teach-main {
    max-width: 1120px;
    margin: 0 auto;
    padding: 28px 16px 64px;
}
</style>

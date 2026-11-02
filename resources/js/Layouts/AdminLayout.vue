<template>
    <v-app>
        <header class="admin-bar">
            <div class="admin-bar__inner">
                <Link :href="route('admin.dashboard')" class="d-flex align-center ga-3 text-decoration-none">
                    <BrandMark />
                    <span class="rq-badge rq-badge--info">{{ $t('admin.title') }}</span>
                </Link>

                <div class="d-flex align-center ga-1">
                    <span v-if="admin" class="admin-bar__who d-none d-sm-inline">{{ admin.name }}</span>
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        :aria-label="$t('admin.nav.two_factor')"
                        @click="router.visit(route('admin.two-factor.setup'))"
                    >
                        <v-icon icon="tabler:shield-lock" />
                    </v-btn>
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        :aria-label="$t('nav.logout')"
                        @click="router.post(route('admin.logout'))"
                    >
                        <v-icon icon="tabler:logout" />
                    </v-btn>
                </div>
            </div>

            <nav class="admin-nav">
                <Link
                    v-for="item in items"
                    :key="item.route"
                    :href="route(item.route)"
                    class="admin-nav__item"
                    :class="{ 'admin-nav__item--active': route().current(item.match) }"
                >
                    <v-icon :icon="item.icon" size="18" />
                    {{ $t(item.label) }}
                </Link>
            </nav>
        </header>

        <v-main class="admin-main">
            <div class="admin-main__inner">
                <FlashStatus />
                <v-alert
                    v-for="(message, key) in actionErrors"
                    :key="key"
                    type="error"
                    variant="tonal"
                    density="comfortable"
                    class="mb-5"
                    rounded="lg"
                >
                    {{ message }}
                </v-alert>
                <slot />
            </div>
        </v-main>
    </v-app>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import BrandMark from '@/Components/BrandMark.vue';
import FlashStatus from '@/Components/FlashStatus.vue';

const page = usePage();
const admin = computed(() => page.props.auth?.admin || null);

// Errors from actions that have no form field (accept twice, link for a suspended account).
const actionErrors = computed(() => {
    const errors = page.props.errors || {};

    return ['application', 'user']
        .filter((key) => errors[key])
        .reduce((all, key) => ({ ...all, [key]: errors[key] }), {});
});

const items = [
    { route: 'admin.dashboard', match: 'admin.dashboard', label: 'admin.nav.dashboard', icon: 'tabler:layout-dashboard' },
    { route: 'admin.applications.index', match: 'admin.applications.*', label: 'admin.nav.applications', icon: 'tabler:file-text' },
    { route: 'admin.instructors.index', match: 'admin.instructors.*', label: 'admin.nav.instructors', icon: 'tabler:school' },
    { route: 'admin.students.index', match: 'admin.students.*', label: 'admin.nav.students', icon: 'tabler:users' },
    { route: 'admin.settings.edit', match: 'admin.settings.*', label: 'admin.nav.settings', icon: 'tabler:settings' },
];
</script>

<style scoped>
.admin-bar {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--rq-surface);
    border-bottom: 1px solid var(--rq-line-soft);
}

.admin-bar__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    height: var(--rq-topbar-height);
    margin: 0 auto;
    padding: 0 16px;
}

.admin-bar__who {
    font-size: 14px;
    color: var(--rq-text-muted);
    margin-inline-end: 8px;
}

.admin-nav {
    display: flex;
    gap: 4px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 12px;
    overflow-x: auto;
    scrollbar-width: none;
}

.admin-nav__item {
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

.admin-nav__item:hover {
    color: var(--rq-text-heading);
}

.admin-nav__item--active {
    border-bottom-color: var(--rq-line-emphasis);
    color: var(--rq-text-heading);
}

.admin-main {
    min-height: 100%;
    background: var(--rq-bg-page);
}

.admin-main__inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 16px 64px;
}
</style>

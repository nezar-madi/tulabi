<template>
    <Head :title="$t('admin.nav.dashboard')" />

    <h1 class="admin-title rq-display">{{ $t('admin.nav.dashboard') }}</h1>

    <div class="admin-stats">
        <component
            :is="card.route ? Link : 'div'"
            v-for="card in cards"
            :key="card.key"
            :href="card.route ? route(card.route, card.params || {}) : undefined"
            class="rq-card admin-stat text-decoration-none"
        >
            <span class="rq-icon-tile" :class="card.tile"><v-icon :icon="card.icon" /></span>
            <span class="admin-stat__number rq-number">{{ stats[card.key] }}</span>
            <span class="admin-stat__label">{{ $t(`admin.dashboard.${card.key}`) }}</span>
        </component>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: { type: Object, required: true },
});

const cards = [
    { key: 'pending_applications', icon: 'tabler:file-text', tile: 'rq-icon-tile--amber', route: 'admin.applications.index' },
    { key: 'instructors', icon: 'tabler:school', tile: '', route: 'admin.instructors.index' },
    { key: 'students', icon: 'tabler:users', tile: 'rq-icon-tile--teal', route: 'admin.students.index' },
    { key: 'suspended', icon: 'tabler:user-off', tile: 'rq-icon-tile--rose' },
];
</script>

<style scoped>
.admin-title {
    margin: 0 0 20px;
    font-size: var(--rq-text-h2);
    font-weight: 700;
    color: var(--rq-text-heading);
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.admin-stat {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 20px;
    border-radius: var(--rq-radius-card);
    color: inherit;
}

.admin-stat__number {
    margin-top: 8px;
    font-family: var(--rq-font-display);
    font-size: 30px;
    font-weight: 700;
    color: var(--rq-text-number);
}

.admin-stat__label {
    color: var(--rq-text-muted);
}
</style>

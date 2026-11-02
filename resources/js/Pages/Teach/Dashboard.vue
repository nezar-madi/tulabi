<template>
    <Head :title="$t('teach.nav.dashboard')" />

    <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-6">
        <h1 class="teach-title rq-display">{{ $t('teach.dashboard.title', { name: profile.display_name }) }}</h1>
        <Link :href="route('teach.classes.create')" class="text-decoration-none">
            <v-btn class="rq-btn rq-btn--primary">
                <v-icon icon="tabler:plus" start />
                {{ $t('teach.dashboard.new_class') }}
            </v-btn>
        </Link>
    </div>

    <section v-if="steps.some((step) => !step.done)" class="rq-card setup">
        <h2 class="setup__title">{{ $t('teach.dashboard.setup_title') }}</h2>
        <ul class="setup__steps">
            <li v-for="step in steps" :key="step.key" :class="{ 'setup__step--done': step.done }">
                <v-icon :icon="step.done ? 'tabler:circle-check' : 'tabler:circle'" size="20" />
                {{ $t(`teach.dashboard.${step.key}`) }}
            </li>
        </ul>
        <Link :href="route('teach.profile.edit')" class="text-decoration-none">
            <v-btn class="rq-btn mt-2" variant="outlined" color="primary">{{ $t('teach.dashboard.go') }}</v-btn>
        </Link>
    </section>

    <div class="teach-stats">
        <Link
            v-for="item in statItems"
            :key="item.status"
            :href="route('teach.classes.index', { filter: item.filter })"
            class="rq-card teach-stat text-decoration-none"
        >
            <span class="teach-stat__number rq-number">{{ counts[item.status] || 0 }}</span>
            <span class="teach-stat__label">{{ $t(`teach.dashboard.counts.${item.status}`) }}</span>
        </Link>
    </div>

    <h2 class="teach-subtitle">{{ $t('teach.dashboard.upcoming') }}</h2>
    <p v-if="upcoming.length === 0" class="rq-card teach-empty">{{ $t('teach.dashboard.no_upcoming') }}</p>
    <div class="class-grid">
        <ClassCard v-for="cls in upcoming" :key="cls.id" :cls="cls" show-status>
            <template #actions>
                <Link :href="route('teach.classes.edit', cls.id)" class="text-decoration-none">
                    <v-btn class="rq-btn" variant="text" color="primary">{{ $t('teach.classes.edit') }}</v-btn>
                </Link>
            </template>
        </ClassCard>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ClassCard from '@/Components/ClassCard.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    upcoming: { type: Array, default: () => [] },
});

const steps = computed(() => [
    { key: 'step_slug', done: Boolean(props.profile.slug) },
    { key: 'step_photo', done: props.profile.has_photo },
    { key: 'step_bio', done: props.profile.has_bio },
]);

const statItems = [
    { status: 'published', filter: 'active' },
    { status: 'closed', filter: 'active' },
    { status: 'draft', filter: 'draft' },
    { status: 'canceled', filter: 'canceled' },
];
</script>

<style scoped src="./teach.css"></style>

<style scoped>
.setup {
    padding: 20px;
    margin-bottom: 16px;
    border-radius: var(--rq-radius-card);
    border-inline-start: 3px solid var(--rq-brand-accent);
}

.setup__title {
    margin: 0 0 10px;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    color: var(--rq-text-heading);
}

.setup__steps {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
    color: var(--rq-text-body);
}

.setup__steps li {
    display: flex;
    align-items: center;
    gap: 8px;
}

.setup__step--done {
    color: var(--rq-text-subtle);
    text-decoration: line-through;
}

.setup__step--done .v-icon {
    color: var(--rq-success);
}

.teach-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}

.teach-stat {
    display: flex;
    flex-direction: column;
    padding: 16px 18px;
    border-radius: var(--rq-radius-card);
    color: inherit;
}

.teach-stat__number {
    font-family: var(--rq-font-display);
    font-size: 26px;
    font-weight: 700;
    color: var(--rq-text-number);
}

.teach-stat__label {
    font-size: 14px;
    color: var(--rq-text-muted);
}
</style>

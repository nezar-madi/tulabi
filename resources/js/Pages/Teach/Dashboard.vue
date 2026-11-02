<template>
    <Head :title="$t('teach.nav.dashboard')" />

    <PageIntro :title="$t('teach.dashboard.title', { name: profile.display_name })" :breadcrumbs="[]">
        <template #actions>
            <CtaButton icon="tabler:plus" @click="router.visit(route('teach.classes.create'))">
                {{ $t('teach.dashboard.new_class') }}
            </CtaButton>
        </template>
    </PageIntro>

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

    <Link :href="route('teach.requests.index')" class="rq-card pending text-decoration-none" :class="{ 'pending--on': pending > 0 }">
        <span class="rq-icon-tile rq-icon-tile--amber"><v-icon icon="tabler:inbox" /></span>
        <span class="flex-grow-1">
            <strong class="pending__title">{{ $t('teach.dashboard.pending_title') }}</strong>
            <span class="pending__text">{{ pending > 0 ? $t('teach.dashboard.pending_open') : $t('teach.dashboard.pending_none') }}</span>
        </span>
        <span class="pending__number rq-number">{{ pending }}</span>
    </Link>

    <div class="money">
        <div class="rq-card money__card">
            <span class="rq-icon-tile rq-icon-tile--green"><v-icon icon="tabler:cash" /></span>
            <span>
                <span class="money__label">{{ $t('teach.dashboard.collected_month') }}</span>
                <strong class="money__number rq-number">{{ $t('money.amount', { amount: money.collected }) }}</strong>
            </span>
        </div>
        <div class="rq-card money__card">
            <span class="rq-icon-tile rq-icon-tile--amber"><v-icon icon="tabler:wallet" /></span>
            <span>
                <span class="money__label">{{ $t('teach.dashboard.remaining_all') }}</span>
                <strong class="money__number rq-number">{{ $t('money.amount', { amount: money.remaining }) }}</strong>
            </span>
        </div>
    </div>

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
                <Link :href="route('teach.classes.students', cls.id)" class="text-decoration-none">
                    <v-btn class="rq-btn" variant="text" color="primary">
                        <v-icon icon="tabler:users" start />
                        {{ $t('teach.dashboard.students_count', { count: cls.students_count }) }}
                    </v-btn>
                </Link>
            </template>
        </ClassCard>
    </div>
</template>

<script setup>
import CtaButton from '@/Components/CtaButton.vue';
import PageIntro from '@/Components/PageIntro.vue';
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ClassCard from '@/Components/ClassCard.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    upcoming: { type: Array, default: () => [] },
    money: { type: Object, default: () => ({ collected: '0', remaining: '0' }) },
});

const page = usePage();
const pending = computed(() => page.props.auth?.user?.pendingRequests || 0);

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

.pending {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    margin-bottom: 12px;
    border-radius: var(--rq-radius-card);
    color: inherit;
}

.pending--on {
    border-inline-start: 3px solid var(--rq-brand-accent);
}

.pending__title {
    display: block;
    font-family: var(--rq-font-display);
    color: var(--rq-text-heading);
}

.pending__text {
    font-size: 14px;
    color: var(--rq-text-muted);
}

.pending__number {
    font-family: var(--rq-font-display);
    font-size: 28px;
    font-weight: 700;
    color: var(--rq-text-number);
}

.money {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.money__card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: var(--rq-radius-card);
}

.money__label {
    display: block;
    font-size: 14px;
    color: var(--rq-text-muted);
}

.money__number {
    font-family: var(--rq-font-display);
    font-size: 24px;
    color: var(--rq-text-number);
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

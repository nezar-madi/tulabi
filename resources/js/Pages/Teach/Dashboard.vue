<template>
    <Head :title="$t('teach.nav.dashboard')" />

    <div class="dashboard">
        <PageIntro
            :title="$t('teach.dashboard.title', { name: profile.display_name })"
            :description="$t('teach.dashboard.lead')"
            :breadcrumbs="[]"
        >
            <template #actions>
                <v-btn
                    v-if="profile.public_url"
                    class="text-none"
                    variant="tonal"
                    color="primary"
                    :href="profile.public_url"
                    target="_blank"
                    rel="noopener"
                    prepend-icon="tabler:world"
                >
                    {{ $t('teach.nav.view_page') }}
                </v-btn>
                <v-btn
                    class="text-none"
                    variant="flat"
                    color="primary"
                    prepend-icon="tabler:plus"
                    @click="router.visit(route('teach.classes.create'))"
                >
                    {{ $t('teach.dashboard.new_class') }}
                </v-btn>
            </template>

            <PageIntroStats :items="kpis" />
        </PageIntro>

        <!-- Setup: what the public page still needs -->
        <section v-if="stepsDone < steps.length" class="dash-section">
            <header class="dash-section__head">
                <div class="min-w-0">
                    <h3 class="dash-section__title rq-display">{{ $t('teach.dashboard.setup_title') }}</h3>
                    <p class="dash-section__hint">{{ $t('teach.dashboard.setup_hint') }}</p>
                </div>
            </header>
            <div class="dash-progress">
                <span class="dash-progress__text">
                    {{ $t('teach.dashboard.setup_progress', { done: stepsDone, total: steps.length }) }}
                </span>
                <span class="dash-progress__bar" aria-hidden="true">
                    <span :style="{ width: `${Math.round((stepsDone / steps.length) * 100)}%` }" />
                </span>
            </div>
            <ol class="dash-list dash-steps">
                <li
                    v-for="step in steps"
                    :key="step.key"
                    class="dash-list__row"
                    :class="{ 'dash-step--done': step.done, 'dash-step--next': step.key === nextStep }"
                >
                    <span class="rq-icon-tile dash-list__tile" :class="{ 'rq-icon-tile--green': step.done }">
                        <v-icon :icon="step.done ? 'tabler:check' : step.icon" size="18" />
                    </span>
                    <div class="dash-list__body min-w-0">
                        <div class="dash-list__title">{{ $t(`teach.dashboard.${step.key}`) }}</div>
                        <div class="dash-list__meta">{{ $t(`teach.dashboard.${step.key}_hint`) }}</div>
                    </div>
                    <span v-if="step.done" class="dash-label dash-label--done flex-shrink-0">{{ $t('teach.dashboard.done') }}</span>
                    <v-btn
                        v-else
                        class="text-none flex-shrink-0"
                        :variant="step.key === nextStep ? 'flat' : 'tonal'"
                        color="primary"
                        size="small"
                        @click="router.visit(route('teach.profile.edit'))"
                    >
                        {{ $t('teach.dashboard.go') }}
                    </v-btn>
                </li>
            </ol>
        </section>

        <div class="dash-grid">
            <!-- Coming classes -->
            <section class="dash-section">
                <header class="dash-section__head">
                    <div class="min-w-0">
                        <h3 class="dash-section__title rq-display">{{ $t('teach.dashboard.upcoming') }}</h3>
                        <p class="dash-section__hint">{{ $t('teach.dashboard.upcoming_hint') }}</p>
                    </div>
                    <v-btn
                        class="text-none flex-shrink-0"
                        variant="text"
                        color="primary"
                        size="small"
                        append-icon="tabler:chevron-left"
                        @click="router.visit(route('teach.classes.index'))"
                    >
                        {{ $t('teach.dashboard.all_classes') }}
                    </v-btn>
                </header>

                <div v-if="upcoming.length === 0" class="dash-empty-state">
                    <span class="rq-icon-tile"><v-icon icon="tabler:chalkboard" /></span>
                    <p>{{ $t('teach.dashboard.no_upcoming') }}</p>
                    <v-btn class="text-none" variant="tonal" color="primary" prepend-icon="tabler:plus" @click="router.visit(route('teach.classes.create'))">
                        {{ $t('teach.dashboard.new_class') }}
                    </v-btn>
                </div>

                <div v-else class="dash-list">
                    <Link
                        v-for="cls in upcoming"
                        :key="cls.id"
                        :href="route('teach.classes.students', cls.id)"
                        class="dash-list__row dash-list__row--link"
                    >
                        <span class="dash-date" aria-hidden="true">
                            <span class="dash-date__day rq-number">{{ day(cls.starts_on) }}</span>
                            <span class="dash-date__month">{{ month(cls.starts_on) }}</span>
                        </span>
                        <div class="dash-list__body min-w-0">
                            <div class="dash-list__title text-truncate">{{ cls.title }}</div>
                            <div class="dash-list__meta">
                                <v-icon :icon="cls.type === 'online' ? 'tabler:video' : 'tabler:map-pin'" size="14" />
                                {{ $t(`class.type.${cls.type}`) }}
                                <span class="dash-sep">·</span>
                                <v-icon icon="tabler:users" size="14" />
                                {{ $t('teach.dashboard.students_count', { count: cls.students_count }) }}
                            </div>
                        </div>
                        <ClassStatusBadge :status="cls.status" :ended="cls.ended" class="flex-shrink-0" />
                    </Link>
                </div>
            </section>

            <!-- Classes by status + the public page -->
            <div class="dash-stack">
                <section class="dash-section">
                    <header class="dash-section__head">
                        <div class="min-w-0">
                            <h3 class="dash-section__title rq-display">{{ $t('teach.dashboard.classes_title') }}</h3>
                            <p class="dash-section__hint">{{ $t('teach.dashboard.classes_hint', { count: totalClasses }) }}</p>
                        </div>
                    </header>
                    <div class="dash-status">
                        <Link
                            v-for="item in statusItems"
                            :key="item.status"
                            :href="route('teach.classes.index', { filter: item.filter })"
                            class="dash-status__cell"
                        >
                            <span class="dash-status__dot" :class="`dash-status__dot--${item.tone}`" aria-hidden="true" />
                            <span class="dash-status__label">{{ $t(`teach.dashboard.counts.${item.status}`) }}</span>
                            <span class="dash-status__value rq-number">{{ counts[item.status] || 0 }}</span>
                        </Link>
                    </div>
                </section>

                <section v-if="profile.public_url" class="dash-section">
                    <header class="dash-section__head">
                        <div class="min-w-0">
                            <h3 class="dash-section__title rq-display">{{ $t('teach.dashboard.page_title') }}</h3>
                            <p class="dash-section__hint">{{ $t('teach.dashboard.page_hint') }}</p>
                        </div>
                    </header>
                    <div class="dash-page">
                        <a :href="profile.public_url" target="_blank" rel="noopener" class="dash-page__url" dir="ltr">
                            {{ displayUrl }}
                        </a>
                        <div class="d-flex flex-wrap ga-2">
                            <CopyButton :text="profile.public_url" :label="$t('teach.dashboard.copy_link')" />
                            <v-btn class="text-none" variant="text" color="primary" prepend-icon="tabler:pencil" @click="router.visit(route('teach.profile.edit'))">
                                {{ $t('teach.dashboard.edit_page') }}
                            </v-btn>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ClassStatusBadge from '@/Components/ClassStatusBadge.vue';
import CopyButton from '@/Components/CopyButton.vue';
import PageIntro from '@/Components/PageIntro.vue';
import PageIntroStats from '@/Components/PageIntroStats.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    upcoming: { type: Array, default: () => [] },
    students: { type: Number, default: 0 },
    money: { type: Object, default: () => ({ collected: '0', remaining: '0' }) },
});

const { t } = useI18n();
const page = usePage();
const pending = computed(() => page.props.auth?.user?.pendingRequests || 0);

const kpis = computed(() => [
    {
        key: 'requests',
        label: t('teach.dashboard.kpi_requests'),
        value: pending.value,
        icon: 'tabler:inbox',
        tone: pending.value > 0 ? 'amber' : 'neutral',
        href: route('teach.requests.index'),
    },
    {
        key: 'students',
        label: t('teach.dashboard.students_kpi'),
        value: props.students,
        icon: 'tabler:users',
        tone: 'sky',
    },
    {
        key: 'collected',
        label: t('teach.dashboard.kpi_collected'),
        value: t('money.amount', { amount: props.money.collected }),
        icon: 'tabler:cash',
        tone: 'success',
    },
    {
        key: 'remaining',
        label: t('teach.dashboard.kpi_remaining'),
        value: t('money.amount', { amount: props.money.remaining }),
        icon: 'tabler:wallet',
        tone: 'warning',
    },
]);

const steps = computed(() => [
    { key: 'step_slug', icon: 'tabler:world', done: Boolean(props.profile.slug) },
    { key: 'step_photo', icon: 'tabler:user', done: props.profile.has_photo },
    { key: 'step_bio', icon: 'tabler:file-text', done: props.profile.has_bio },
]);
const stepsDone = computed(() => steps.value.filter((step) => step.done).length);
const nextStep = computed(() => steps.value.find((step) => !step.done)?.key);

const statusItems = [
    { status: 'published', filter: 'active', tone: 'success' },
    { status: 'closed', filter: 'active', tone: 'warning' },
    { status: 'draft', filter: 'draft', tone: 'neutral' },
    { status: 'canceled', filter: 'canceled', tone: 'danger' },
];
const totalClasses = computed(() => statusItems.reduce((sum, item) => sum + Number(props.counts[item.status] || 0), 0));

const displayUrl = computed(() => String(props.profile.public_url || '').replace(/^https?:\/\//, ''));

// The date tile of a coming class: day number over the short Arabic month.
function parse(value) {
    const [y, m, d] = String(value || '').split('-').map(Number);

    return y ? new Date(Date.UTC(y, m - 1, d)) : null;
}

function day(value) {
    return parse(value)?.getUTCDate() ?? '—';
}

function month(value) {
    const date = parse(value);

    return date ? new Intl.DateTimeFormat('ar-LY-u-nu-latn', { month: 'short', timeZone: 'UTC' }).format(date) : '';
}
</script>

<style scoped>
.dashboard {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-6);
}

/* Sections: white card, hairline, header strip (as rqmana's dashboard) */
.dash-section {
    min-width: 0;
    overflow: hidden;
    background: var(--rq-surface);
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-card);
}

.dash-section__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--rq-space-4);
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--rq-line-soft);
}

.dash-section__title {
    margin: 0;
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    line-height: 1.45;
    color: var(--rq-text-heading);
}

.dash-section__hint {
    margin: 4px 0 0;
    font-size: var(--rq-text-label);
    line-height: 1.6;
    color: var(--rq-text-muted);
}

.dash-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--rq-space-6);
    align-items: start;
}

.dash-stack {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-6);
    min-width: 0;
}

/* Lists: full-width rows split by hairlines, tint on hover */
.dash-list {
    display: flex;
    flex-direction: column;
    margin: 0;
    padding: 0;
    list-style: none;
}

.dash-list__row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 24px;
    border-top: 1px solid var(--rq-line-soft);
    color: inherit;
    text-decoration: none;
    transition: background-color var(--rq-dur-fast) var(--rq-ease);
}

.dash-list__row:first-child {
    border-top: 0;
}

.dash-list__row:hover {
    background: var(--rq-surface-hover);
}

.dash-list__row--link:focus-visible {
    outline: 2px solid var(--rq-text-link);
    outline-offset: -2px;
}

.dash-list__body {
    flex: 1 1 auto;
}

.dash-list__tile {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
}

.dash-list__title {
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-small);
    font-weight: 600;
    line-height: 1.5;
    color: var(--rq-text-heading);
}

.dash-list__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    font-size: var(--rq-text-label);
    line-height: 1.5;
    color: var(--rq-text-muted);
}

.dash-sep {
    margin-inline: 4px;
    color: var(--rq-text-subtle);
}

/* Setup steps */
.dash-progress {
    display: flex;
    align-items: center;
    gap: var(--rq-space-4);
    padding: var(--rq-space-4) var(--rq-space-6) var(--rq-space-2);
}

.dash-progress__text {
    flex: 0 0 auto;
    font-size: 13px;
    font-weight: 500;
    color: var(--rq-text-body);
}

.dash-progress__bar {
    flex: 1 1 auto;
    height: 6px;
    overflow: hidden;
    border-radius: 999px;
    background: var(--rq-surface-tint);
}

.dash-progress__bar span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--rq-success);
    transition: width var(--rq-dur) var(--rq-ease);
}

.dash-step--done .dash-list__title {
    color: var(--rq-text-muted);
}

.dash-step--next {
    background: var(--rq-surface-hover);
}

.dash-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: var(--rq-radius-sm);
    font-family: var(--rq-font-display);
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
}

.dash-label::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.dash-label--done {
    background: var(--rq-success-tint);
    color: var(--rq-success);
}

/* Coming classes: a calendar tile with the start date */
.dash-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 48px;
    height: 52px;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-tint);
    line-height: 1.1;
}

.dash-date__day {
    font-family: var(--rq-font-display);
    font-size: 19px;
    font-weight: 700;
    color: var(--rq-text-number);
}

.dash-date__month {
    font-size: 11px;
    color: var(--rq-text-muted);
}

.dash-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--rq-space-3);
    padding: var(--rq-space-8, 32px) var(--rq-space-6);
    text-align: center;
}

.dash-empty-state p {
    margin: 0;
    font-size: var(--rq-text-small);
    color: var(--rq-text-muted);
}

/* Classes by status: a 2 x 2 strip with hairline dividers */
.dash-status {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.dash-status__cell {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid var(--rq-line-soft);
    color: inherit;
    text-decoration: none;
    transition: background-color var(--rq-dur-fast) var(--rq-ease);
}

.dash-status__cell:nth-child(-n + 2) {
    border-top: 0;
}

.dash-status__cell:nth-child(even) {
    border-inline-start: 1px solid var(--rq-line-soft);
}

.dash-status__cell:hover {
    background: var(--rq-surface-hover);
}

.dash-status__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dash-status__dot--success { background: var(--rq-success); }
.dash-status__dot--warning { background: var(--rq-warning); }
.dash-status__dot--neutral { background: var(--rq-text-subtle); }
.dash-status__dot--danger { background: var(--rq-danger); }

.dash-status__label {
    flex: 1 1 auto;
    font-size: var(--rq-text-label);
    color: var(--rq-text-muted);
}

.dash-status__value {
    font-family: var(--rq-font-display);
    font-size: 20px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

/* The public page link */
.dash-page {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-3);
    padding: 16px 24px 20px;
}

.dash-page__url {
    display: block;
    padding: 10px 14px;
    overflow: hidden;
    border: 1px dashed var(--rq-line-emphasis);
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-tint);
    font-size: 14px;
    font-weight: 600;
    color: var(--rq-text-link);
    text-align: left;
    text-decoration: none;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dash-page__url:hover {
    text-decoration: underline;
}

@media (max-width: 599.98px) {
    .dash-section__head,
    .dash-list__row,
    .dash-status__cell,
    .dash-page {
        padding-inline: var(--rq-space-4);
    }

    .dash-progress {
        padding-inline: var(--rq-space-4);
    }
}

@media (min-width: 960px) {
    .dash-grid {
        grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
    }
}
</style>

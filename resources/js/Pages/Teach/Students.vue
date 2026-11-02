<template>
    <Head :title="$t('teach.roster.title')" />

    <div class="roster">
        <PageIntro :title="$t('teach.roster.title')" :description="$t('teach.roster.lead')">
            <PageIntroStats :items="stats" />
        </PageIntro>

        <!-- Filters: status tabs, course, search -->
        <div class="roster__bar">
            <div class="teach-tabs roster__tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    type="button"
                    class="teach-tabs__tab"
                    :class="{ 'teach-tabs__tab--active': filter === tab }"
                    @click="apply({ filter: tab })"
                >
                    {{ $t(`teach.roster.filters.${tab}`) }}
                    <span class="roster__count rq-number">{{ counts[tab] }}</span>
                </button>
            </div>

            <div class="roster__fields">
                <v-select
                    :model-value="course"
                    :items="courseItems"
                    item-title="title"
                    item-value="id"
                    :label="$t('teach.roster.course')"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    class="roster__course"
                    @update:model-value="apply({ course: $event })"
                />
                <v-text-field
                    v-model="search"
                    :label="$t('teach.roster.search')"
                    prepend-inner-icon="tabler:search"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    clearable
                    class="roster__search"
                    @update:model-value="searchSoon"
                />
            </div>
        </div>

        <p v-if="students.data.length === 0" class="rq-card teach-empty">
            {{ q || course ? $t('teach.roster.empty_search') : $t('teach.roster.empty') }}
        </p>

        <article v-for="student in students.data" :key="student.user_id" class="rq-card student" :class="{ 'student--muted': !student.is_active }">
            <div class="student__top">
                <span class="student__avatar" aria-hidden="true">{{ initial(student) }}</span>
                <div class="student__main">
                    <h2 class="student__name">
                        <template v-if="student.has_name">{{ student.name }}</template>
                        <span v-else class="student__noname">{{ $t('teach.roster.no_name') }}</span>
                        <span v-if="student.is_instructor" class="rq-badge ms-2">{{ $t('teach.roster.instructor') }}</span>
                        <span v-if="!student.account_active" class="rq-badge rq-badge--danger ms-2">{{ $t('teach.roster.suspended') }}</span>
                    </h2>
                    <p class="student__meta">
                        <span dir="ltr">{{ student.phone }}</span>
                        <span class="student__sep">·</span>
                        {{ $t('teach.roster.courses_count', { count: student.courses_count }) }}
                        <template v-if="student.active_count">
                            ({{ $t('teach.roster.active_count', { count: student.active_count }) }})
                        </template>
                    </p>
                </div>
                <div class="student__money">
                    <span class="student__money-cell">
                        <small>{{ $t('teach.roster.paid') }}</small>
                        <strong class="rq-number">{{ $t('money.amount', { amount: student.paid }) }}</strong>
                    </span>
                    <span class="student__money-cell" :class="{ 'student__money-cell--due': Number(student.remaining) > 0 }">
                        <small>{{ $t('teach.roster.remaining') }}</small>
                        <strong class="rq-number">{{ $t('money.amount', { amount: student.remaining }) }}</strong>
                    </span>
                </div>
            </div>

            <div class="student__actions">
                <WhatsAppButton :href="student.whatsapp" />
                <v-btn
                    v-if="student.reset_enrollment_id && !student.is_instructor && student.account_active"
                    class="rq-btn"
                    variant="outlined"
                    color="primary"
                    @click="resetLinkFor(student)"
                >
                    <v-icon icon="tabler:key" start />
                    {{ $t('teach.roster.reset_link') }}
                </v-btn>
                <v-btn class="rq-btn ms-auto" variant="text" color="primary" @click="toggle(student.user_id)">
                    {{ open.has(student.user_id) ? $t('teach.roster.hide_courses') : $t('teach.roster.show_courses') }}
                    <v-icon :icon="open.has(student.user_id) ? 'tabler:chevron-up' : 'tabler:chevron-down'" end />
                </v-btn>
            </div>

            <!-- The student's places, one row per course -->
            <ul v-if="open.has(student.user_id)" class="places">
                <li v-for="place in student.enrollments" :key="place.id" class="places__row">
                    <div class="places__main">
                        <Link :href="route('teach.classes.students', place.class.id)" class="places__title">{{ place.class.title }}</Link>
                        <EnrollmentStatusBadge :status="place.status" for-instructor class="ms-2" />
                        <p class="places__meta">
                            <template v-if="place.accepted_at">{{ $t('teach.roster.accepted_on', { date: formatDate(place.accepted_at) }) }} · </template>
                            {{ $t('teach.roster.fee') }}: {{ $t('money.amount', { amount: place.fee ?? '0' }) }}
                            · {{ $t('teach.roster.paid') }}: {{ $t('money.amount', { amount: place.paid }) }}
                            <template v-if="place.status === 'accepted'">
                                · {{ $t('teach.roster.remaining') }}: {{ $t('money.amount', { amount: place.remaining }) }}
                            </template>
                        </p>
                    </div>
                    <div class="places__actions">
                        <Link
                            v-if="place.status === 'accepted' || Number(place.paid) > 0"
                            :href="route('teach.enrollments.payments', place.id)"
                            class="text-decoration-none"
                        >
                            <v-btn class="rq-btn" variant="tonal" color="primary" size="small">
                                <v-icon icon="tabler:cash" start />
                                {{ $t('teach.roster.payments') }}
                            </v-btn>
                        </Link>
                        <v-btn
                            v-if="place.status === 'accepted'"
                            class="rq-btn"
                            variant="text"
                            color="error"
                            size="small"
                            @click="openRemove(student, place)"
                        >
                            {{ $t('teach.roster.remove') }}
                        </v-btn>
                    </div>
                </li>
            </ul>
        </article>

        <Pager :paginator="students" />
    </div>

    <ReasonDialog
        v-model="removeOpen"
        :url="removeRow ? route('teach.enrollments.remove', removeRow.id) : ''"
        :title="$t('teach.students.remove_title', { name: removeName })"
        :text="$t('teach.students.remove_text')"
        :confirm-label="$t('teach.students.remove')"
    />

    <ResetLinkDialog
        :link="resetLink"
        :title="$t('teach.students.reset_title', { name: resetLink?.name || '' })"
        :text="$t('teach.students.reset_text')"
        :whatsapp-label="$t('teach.students.send_whatsapp')"
    />
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import EnrollmentStatusBadge from '@/Components/EnrollmentStatusBadge.vue';
import Pager from '@/Components/Pager.vue';
import PageIntro from '@/Components/PageIntro.vue';
import PageIntroStats from '@/Components/PageIntroStats.vue';
import ReasonDialog from '@/Components/ReasonDialog.vue';
import ResetLinkDialog from '@/Components/ResetLinkDialog.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';
import { formatDate } from '@/utils/dates';

const props = defineProps({
    students: { type: Object, required: true },
    filter: { type: String, default: 'active' },
    q: { type: String, default: '' },
    course: { type: Number, default: null },
    courses: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ active: 0, past: 0, all: 0 }) },
    totals: { type: Object, default: () => ({ remaining: '0' }) },
    resetLink: { type: Object, default: null },
});

const { t } = useI18n();
const tabs = ['active', 'past', 'all'];

const stats = computed(() => [
    { key: 'active', label: t('teach.roster.stat_active'), value: props.counts.active, icon: 'tabler:users', tone: 'sky' },
    { key: 'all', label: t('teach.roster.stat_all'), value: props.counts.all, icon: 'tabler:user-check', tone: 'teal' },
    {
        key: 'remaining',
        label: t('teach.roster.stat_remaining'),
        value: t('money.amount', { amount: props.totals.remaining }),
        icon: 'tabler:wallet',
        tone: 'warning',
    },
]);

const courseItems = computed(() => [{ id: null, title: t('teach.roster.all_courses') }, ...props.courses]);

// Filters live in the address, so a page can be shared or reloaded as it is.
function apply(change) {
    const next = { filter: props.filter, q: props.q || undefined, course: props.course || undefined, ...change };

    router.get(route('teach.students.index'), {
        filter: next.filter === 'active' ? undefined : next.filter,
        q: next.q || undefined,
        course: next.course || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

const search = ref(props.q);
let timer = null;

function searchSoon(value) {
    clearTimeout(timer);
    timer = setTimeout(() => apply({ q: value || undefined }), 350);
}

onBeforeUnmount(() => clearTimeout(timer));

const open = ref(new Set());

function toggle(id) {
    const next = new Set(open.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    open.value = next;
}

function initial(student) {
    return student.has_name ? student.name.trim().charAt(0) : '#';
}

function resetLinkFor(student) {
    router.post(route('teach.enrollments.reset-link', student.reset_enrollment_id), {}, { preserveScroll: true });
}

const removeOpen = ref(false);
const removeRow = ref(null);
const removeName = ref('');

function openRemove(student, place) {
    removeRow.value = place;
    removeName.value = student.name;
    removeOpen.value = true;
}
</script>

<style scoped src="./teach.css"></style>

<style scoped>
.roster {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-4);
}

.roster__bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.roster__tabs {
    margin-bottom: 0;
}

/* The tabs here are buttons (they keep the search and course in the address) */
.roster__tabs .teach-tabs__tab {
    border-top: 0;
    border-inline: 0;
    background: none;
    cursor: pointer;
}

.roster__count {
    margin-inline-start: 6px;
    padding: 1px 7px;
    border-radius: 999px;
    background: var(--rq-surface-tint);
    font-size: 12px;
    color: var(--rq-text-muted);
}

.roster__fields {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.roster__course {
    width: 200px;
}

.roster__search {
    width: 240px;
}

.student {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 18px 20px;
    border-radius: var(--rq-radius-card);
}

.student--muted .student__name {
    color: var(--rq-text-muted);
}

.student__top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14px;
}

.student__avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--rq-surface-tint);
    font-family: var(--rq-font-display);
    font-weight: 700;
    color: var(--rq-text-number);
}

.student__main {
    flex: 1 1 220px;
    min-width: 0;
}

.student__name {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-heading);
}

.student__noname {
    font-weight: 400;
    color: var(--rq-text-subtle);
}

.student__meta {
    margin: 2px 0 0;
    font-size: 14px;
    color: var(--rq-text-muted);
}

.student__sep {
    margin-inline: 4px;
    color: var(--rq-text-subtle);
}

.student__money {
    display: flex;
    gap: 20px;
}

.student__money-cell {
    display: flex;
    flex-direction: column;
}

.student__money-cell small {
    font-size: 12px;
    color: var(--rq-text-muted);
}

.student__money-cell strong {
    font-family: var(--rq-font-display);
    font-size: 17px;
    color: var(--rq-text-number);
}

.student__money-cell--due strong {
    color: var(--rq-warning);
}

.student__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.places {
    margin: 0;
    padding: 0;
    list-style: none;
    border: 1px solid var(--rq-line-soft);
    border-radius: var(--rq-radius-md);
    overflow: hidden;
}

.places__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 16px;
    border-top: 1px solid var(--rq-line-soft);
}

.places__row:first-child {
    border-top: 0;
}

.places__main {
    flex: 1 1 260px;
    min-width: 0;
}

.places__title {
    font-family: var(--rq-font-display);
    font-weight: 600;
    color: var(--rq-text-link);
    text-decoration: none;
}

.places__title:hover {
    text-decoration: underline;
}

.places__meta {
    margin: 2px 0 0;
    font-size: 13px;
    color: var(--rq-text-muted);
}

.places__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

@media (max-width: 599.98px) {
    .roster__course,
    .roster__search {
        width: 100%;
    }

    .roster__fields {
        width: 100%;
    }
}
</style>

<template>
    <Head :title="$t('teach.students.title', { title: item.title })" />

    <PageIntro
        :breadcrumbs="[
            { title: $t('nav.home'), href: route('teach.dashboard') },
            { title: $t('teach.nav.classes'), href: route('teach.classes.index') },
            { title: item.title, href: route('teach.classes.edit', item.id) },
            { title: $t('teach.students.open') },
        ]"
        :title="$t('teach.students.title', { title: item.title })"
    >
        <template #actions>
            <div class="d-flex flex-wrap ga-2">
                <v-btn
                    v-if="item.meeting_share_url && counts.accepted"
                    class="text-none"
                    variant="text"
                    color="success"
                    prepend-icon="tabler:brand-whatsapp"
                    :href="item.meeting_share_url"
                    target="_blank"
                    rel="noopener"
                >
                    {{ $t('teach.classes.meeting.share') }}
                </v-btn>
                <Link :href="route('teach.classes.edit', item.id)" class="text-decoration-none">
                    <v-btn class="text-none" variant="text" color="primary" prepend-icon="tabler:pencil">
                        {{ $t('teach.students.edit_class') }}
                    </v-btn>
                </Link>
            </div>
        </template>

        <!-- The groups, as tabs with their counts -->
        <PageIntroStats v-model="filter" :items="tiles" selectable />
    </PageIntro>

    <!-- Money of the accepted students: collected against the fees -->
    <section v-if="money.fees > 0 || money.paid > 0" class="rq-card sx-money">
        <div class="sx-money__figure">
            <span class="sx-money__label">{{ $t('teach.students.collected') }}</span>
            <strong class="sx-money__value sx-money__value--paid rq-number">{{ amount(money.paid) }}</strong>
        </div>
        <div class="sx-money__figure">
            <span class="sx-money__label">{{ $t('teach.students.still_owed') }}</span>
            <strong class="sx-money__value rq-number" :class="{ 'sx-money__value--due': money.remaining > 0 }">{{ amount(money.remaining) }}</strong>
        </div>
        <div class="sx-money__progress">
            <div class="sx-money__progress-head">
                <span>{{ $t('teach.students.collection_rate') }}</span>
                <strong class="rq-number">{{ money.share }}%</strong>
            </div>
            <span class="sx-bar" aria-hidden="true"><span :style="{ width: `${money.share}%` }" /></span>
        </div>
    </section>

    <EmptyState
        v-if="enrollments.length === 0"
        icon="tabler:users"
        :title="$t('teach.students.empty_title')"
        :text="$t('teach.students.empty_text')"
    />

    <section v-else class="rq-card sx-list">
        <header class="sx-list__head">
            <h2 class="sx-list__title">
                {{ $t(`teach.students.filters.${filter}`) }}
                <span class="sx-list__count rq-number">{{ visible.length }}</span>
            </h2>
            <div class="sx-search">
                <v-icon icon="tabler:search" size="17" class="sx-search__icon" />
                <input
                    v-model="search"
                    type="search"
                    class="sx-search__input"
                    :placeholder="$t('teach.students.search')"
                    :aria-label="$t('teach.students.search')"
                >
            </div>
        </header>

        <!-- Column names (wide screens) -->
        <div class="sx-row sx-row--labels" aria-hidden="true">
            <span>{{ $t('teach.students.col_student') }}</span>
            <span>{{ $t('teach.students.col_status') }}</span>
            <span>{{ $t('teach.students.col_money') }}</span>
            <span />
        </div>

        <p v-if="visible.length === 0" class="sx-none">{{ $t('teach.students.none_here') }}</p>

        <article
            v-for="row in visible"
            :key="row.id"
            class="sx-row"
            :class="{ 'sx-row--past': isPast(row), 'sx-row--waiting': row.status === 'requested' }"
        >
            <!-- Who -->
            <div class="sx-who">
                <span class="sx-avatar" :class="`sx-avatar--${row.status}`" aria-hidden="true">{{ initial(row) }}</span>
                <div class="sx-who__text">
                    <strong class="sx-who__name">{{ row.student?.name || $t('teach.students.no_name') }}</strong>
                    <span class="sx-who__phone" dir="ltr">{{ row.student?.phone }}</span>
                </div>
            </div>

            <!-- Where they stand -->
            <div class="sx-state">
                <EnrollmentStatusBadge :status="row.status" for-instructor />
                <small class="sx-state__date">{{ stateDate(row) }}</small>
            </div>

            <!-- Money -->
            <div class="sx-pay">
                <template v-if="row.status === 'accepted' || Number(row.totals.paid) > 0">
                    <div class="sx-pay__line">
                        <span class="rq-number">{{ $t('teach.students.paid_of', { paid: row.totals.paid, fee: row.totals.fee ?? '0' }) }}</span>
                        <span v-if="Number(row.totals.remaining) > 0" class="sx-pay__due rq-number">{{ $t('teach.students.owes', { amount: row.totals.remaining }) }}</span>
                        <span v-else-if="Number(row.totals.fee) > 0" class="sx-pay__done">
                            <v-icon icon="tabler:circle-check" size="14" />
                            {{ $t('teach.students.paid_full') }}
                        </span>
                        <span v-else class="sx-pay__free">{{ $t('teach.students.free') }}</span>
                    </div>
                    <span v-if="Number(row.totals.fee) > 0" class="sx-bar sx-bar--thin" aria-hidden="true">
                        <span :style="{ width: `${paidShare(row)}%` }" />
                    </span>
                </template>
                <span v-else class="sx-pay__none">—</span>
            </div>

            <!-- What to do -->
            <div class="sx-actions">
                <template v-if="row.status === 'requested'">
                    <v-btn class="text-none" variant="flat" color="primary" size="small" prepend-icon="tabler:check" @click="post('accept', row)">
                        {{ $t('teach.requests.accept') }}
                    </v-btn>
                    <v-btn class="text-none" variant="text" color="error" size="small" @click="openReason('reject', row)">
                        {{ $t('teach.requests.reject') }}
                    </v-btn>
                </template>
                <Link
                    v-else-if="row.status === 'accepted' || Number(row.totals.paid) > 0"
                    :href="route('teach.enrollments.payments', row.id)"
                    class="text-decoration-none"
                >
                    <v-btn class="text-none" variant="tonal" color="primary" size="small" prepend-icon="tabler:cash">
                        {{ $t('teach.students.payments') }}
                    </v-btn>
                </Link>

                <v-btn
                    v-if="row.student?.whatsapp"
                    icon
                    variant="text"
                    size="small"
                    class="sx-wa"
                    :href="row.student.whatsapp"
                    target="_blank"
                    rel="noopener noreferrer"
                    :aria-label="$t('common.whatsapp')"
                >
                    <v-icon icon="tabler:brand-whatsapp" size="19" />
                    <v-tooltip activator="parent" location="top">{{ $t('common.whatsapp') }}</v-tooltip>
                </v-btn>

                <v-menu v-if="row.status === 'accepted'" location="bottom end">
                    <template #activator="{ props: menuProps }">
                        <v-btn v-bind="menuProps" icon variant="text" size="small" :aria-label="$t('teach.money.more')">
                            <v-icon icon="tabler:dots-vertical" size="19" />
                        </v-btn>
                    </template>
                    <v-list density="compact">
                        <v-list-item class="sx-menu-item" @click="post('reset-link', row)">
                            <v-list-item-title>{{ $t('teach.students.reset_link') }}</v-list-item-title>
                            <template #append><v-icon icon="tabler:key" size="16" /></template>
                        </v-list-item>
                        <v-list-item class="sx-menu-item" base-color="error" @click="openReason('remove', row)">
                            <v-list-item-title>{{ $t('teach.students.remove') }}</v-list-item-title>
                            <template #append><v-icon icon="tabler:user-minus" size="16" /></template>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </div>

            <!-- What the student wrote, and why a decision was taken -->
            <p v-if="row.note" class="sx-note">
                <v-icon icon="tabler:note" size="15" />
                <span>{{ row.note }}</span>
            </p>
            <p v-if="row.decision_reason && isPast(row)" class="sx-note sx-note--reason">
                <v-icon icon="tabler:info-circle" size="15" />
                <span>{{ row.decision_reason }}</span>
            </p>
        </article>
    </section>

    <ReasonDialog
        v-model="reasonOpen"
        :url="reasonRow ? route(`teach.enrollments.${reasonAction}`, reasonRow.id) : ''"
        :title="reasonAction === 'remove'
            ? $t('teach.students.remove_title', { name: reasonRow?.student?.name || '' })
            : $t('teach.requests.reject_title', { name: reasonRow?.student?.name || '' })"
        :text="reasonAction === 'remove' ? $t('teach.students.remove_text') : ''"
        :confirm-label="reasonAction === 'remove' ? $t('teach.students.remove') : $t('teach.requests.reject')"
    />

    <ResetLinkDialog
        :link="resetLink"
        :title="$t('teach.students.reset_title', { name: resetLink?.name || '' })"
        :text="$t('teach.students.reset_text')"
        :whatsapp-label="$t('teach.students.send_whatsapp')"
    />
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import PageIntroStats from '@/Components/PageIntroStats.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import EnrollmentStatusBadge from '@/Components/EnrollmentStatusBadge.vue';
import ReasonDialog from '@/Components/ReasonDialog.vue';
import ResetLinkDialog from '@/Components/ResetLinkDialog.vue';
import { formatDate } from '@/utils/dates';

const props = defineProps({
    item: { type: Object, required: true },
    enrollments: { type: Array, default: () => [] },
    resetLink: { type: Object, default: null },
});

const { t } = useI18n();

// Left the class one way or another: rejected, withdrew, removed, or the class was canceled.
const isPast = (row) => !['requested', 'accepted'].includes(row.status);

const counts = computed(() => ({
    all: props.enrollments.length,
    requested: props.enrollments.filter((row) => row.status === 'requested').length,
    accepted: props.enrollments.filter((row) => row.status === 'accepted').length,
    past: props.enrollments.filter(isPast).length,
}));

// Open on the requests when some are waiting, else on the accepted students.
const filter = ref(counts.value.requested > 0 ? 'requested' : (counts.value.accepted > 0 ? 'accepted' : 'all'));

const tiles = computed(() => [
    { key: 'requested', label: t('teach.students.filters.requested'), value: counts.value.requested, icon: 'tabler:clock', tone: 'warning' },
    {
        key: 'accepted',
        label: t('teach.students.filters.accepted'),
        value: props.item.seats ? t('teach.students.of_seats_short', { accepted: counts.value.accepted, seats: props.item.seats }) : counts.value.accepted,
        icon: 'tabler:user-check',
        tone: props.item.seats && counts.value.accepted > props.item.seats ? 'danger' : 'success',
    },
    { key: 'past', label: t('teach.students.filters.past'), value: counts.value.past, icon: 'tabler:user-off', tone: 'neutral' },
    { key: 'all', label: t('teach.students.filters.all'), value: counts.value.all, icon: 'tabler:users', tone: 'sky' },
]);

const search = ref('');

const visible = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.enrollments
        .filter((row) => filter.value === 'all' || (filter.value === 'past' ? isPast(row) : row.status === filter.value))
        .filter((row) => !q
            || (row.student?.name || '').toLowerCase().includes(q)
            || (row.student?.phone || '').replace(/\s/g, '').includes(q.replace(/\s/g, '')));
});

// Amounts are sent as text ("300", "12.5"); added up in thousandths so nothing is lost.
const millis = (value) => Math.round(Number(value || 0) * 1000);

const money = computed(() => {
    const accepted = props.enrollments.filter((row) => row.status === 'accepted');
    const fees = accepted.reduce((sum, row) => sum + millis(row.totals.fee), 0);
    const paid = props.enrollments.reduce((sum, row) => sum + millis(row.totals.paid), 0);
    const remaining = accepted.reduce((sum, row) => sum + millis(row.totals.remaining), 0);

    return {
        fees,
        paid,
        remaining,
        share: fees > 0 ? Math.min(100, Math.round(((fees - remaining) / fees) * 100)) : 0,
    };
});

const amount = (value) => t('money.amount', { amount: String(Number((value / 1000).toFixed(3))) });

function paidShare(row) {
    const fee = millis(row.totals.fee);

    return fee > 0 ? Math.min(100, Math.round((millis(row.totals.paid) / fee) * 100)) : 0;
}

// The first letter of the name (or a dash for someone who has not written one yet).
const initial = (row) => (row.student?.name || '').trim().charAt(0) || '؟';

function stateDate(row) {
    if (row.status === 'accepted' && row.accepted_at) {
        return t('teach.students.accepted_on', { date: formatDate(row.accepted_at) });
    }

    return row.requested_at ? t('teach.students.requested_on', { date: formatDate(row.requested_at) }) : '';
}

function post(action, row) {
    router.post(route(`teach.enrollments.${action}`, row.id), {}, { preserveScroll: true });
}

const reasonOpen = ref(false);
const reasonAction = ref('reject');
const reasonRow = ref(null);

function openReason(action, row) {
    reasonAction.value = action;
    reasonRow.value = row;
    reasonOpen.value = true;
}
</script>

<style scoped src="../teach.css"></style>

<style scoped>
/* ---------- Money summary ---------- */
.sx-money {
    display: grid;
    grid-template-columns: auto auto minmax(0, 1fr);
    align-items: center;
    gap: 16px 32px;
    margin-bottom: var(--rq-space-5);
    padding: 16px 20px;
    border-radius: var(--rq-radius-card);
}

.sx-money__figure {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.sx-money__label {
    font-size: 12px;
    color: var(--rq-text-muted);
}

.sx-money__value {
    font-family: var(--rq-font-display);
    font-size: 20px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

.sx-money__value--paid {
    color: var(--rq-success);
}

.sx-money__value--due {
    color: var(--rq-warning);
}

.sx-money__progress {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}

.sx-money__progress-head {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--rq-text-muted);
}

.sx-money__progress-head strong {
    color: var(--rq-text-heading);
}

.sx-bar {
    display: block;
    height: 8px;
    overflow: hidden;
    border-radius: 999px;
    background: var(--rq-surface-tint);
}

.sx-bar span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--rq-success);
    transition: width var(--rq-dur) var(--rq-ease);
}

.sx-bar--thin {
    height: 5px;
}

/* ---------- The list ---------- */
.sx-list {
    padding: 0;
    overflow: hidden;
    border-radius: var(--rq-radius-card);
}

.sx-list__head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--rq-line-soft);
}

.sx-list__title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: 16px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

.sx-list__count {
    padding: 1px 9px;
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface-tint);
    font-size: 12px;
    color: var(--rq-text-muted);
}

.sx-search {
    position: relative;
    flex: 0 1 260px;
    min-width: 180px;
}

.sx-search__icon {
    position: absolute;
    top: 50%;
    inset-inline-start: 12px;
    color: var(--rq-text-subtle);
    transform: translateY(-50%);
    pointer-events: none;
}

.sx-search__input {
    width: 100%;
    height: 36px;
    padding-inline: 36px 14px;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface);
    font: inherit;
    font-size: 14px;
    color: var(--rq-text-body);
}

.sx-search__input:focus {
    outline: none;
    border-color: var(--rq-action-primary);
    box-shadow: var(--rq-focus-ring);
}

/* A row: student, state, money, actions; notes take the full width under it */
.sx-row {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr) minmax(0, 1.3fr) auto;
    align-items: center;
    gap: 6px 16px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--rq-line-soft);
    transition: background-color var(--rq-dur-fast) var(--rq-ease);
}

.sx-row:last-child {
    border-bottom: 0;
}

.sx-row:not(.sx-row--labels):hover {
    background: var(--rq-surface-hover);
}

.sx-row--labels {
    padding-block: 8px;
    background: var(--rq-surface-hover);
    font-size: 12px;
    font-weight: 600;
    color: var(--rq-text-muted);
}

.sx-row--waiting {
    box-shadow: inset 3px 0 0 var(--rq-warning);
}

[dir='rtl'] .sx-row--waiting {
    box-shadow: inset -3px 0 0 var(--rq-warning);
}

.sx-row--past .sx-who,
.sx-row--past .sx-pay {
    opacity: 0.6;
}

.sx-none {
    margin: 0;
    padding: 28px 20px;
    font-size: 14px;
    text-align: center;
    color: var(--rq-text-muted);
}

.sx-who {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.sx-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--rq-icon-tile);
    font-family: var(--rq-font-display);
    font-size: 16px;
    font-weight: 700;
    color: var(--rq-action-primary);
}

.sx-avatar--requested {
    background: var(--rq-warning-tint);
    color: var(--rq-warning);
}

.sx-avatar--accepted {
    background: var(--rq-success-tint);
    color: var(--rq-success);
}

.sx-who__text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.sx-who__name {
    overflow: hidden;
    font-family: var(--rq-font-display);
    font-size: 15px;
    font-weight: 600;
    color: var(--rq-text-heading);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sx-who__phone {
    align-self: flex-start;
    font-size: 13px;
    color: var(--rq-text-muted);
}

.sx-state {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
}

.sx-state__date {
    font-size: 12px;
    color: var(--rq-text-subtle);
}

.sx-pay {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
    font-size: 13px;
    color: var(--rq-text-body);
}

.sx-pay__line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px 10px;
}

.sx-pay__due {
    font-weight: 600;
    color: var(--rq-warning);
}

.sx-pay__done {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-weight: 600;
    color: var(--rq-success);
}

.sx-pay__free,
.sx-pay__none {
    color: var(--rq-text-subtle);
}

.sx-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
}

.sx-wa {
    color: #1da851;
}

.sx-note {
    grid-column: 1 / -1;
    display: flex;
    align-items: flex-start;
    gap: 6px;
    margin: 4px 0 0;
    padding: 8px 12px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-size: 13px;
    line-height: 1.7;
    color: var(--rq-text-body);
    white-space: pre-line;
}

.sx-note .v-icon {
    flex-shrink: 0;
    margin-top: 3px;
    color: var(--rq-text-subtle);
}

.sx-note--reason {
    background: none;
    padding-block: 0;
    color: var(--rq-text-muted);
}

/* The small items of the ⋮ menu */
.sx-menu-item {
    min-height: 34px !important;
}

.sx-menu-item :deep(.v-list-item-title) {
    font-size: 13px;
    font-weight: 500;
}

.sx-menu-item :deep(.v-list-item__append) {
    margin-inline-start: 10px;
}

/* ---------- Narrow screens: each student is a small card ---------- */
@media (max-width: 899.98px) {
    .sx-row--labels {
        display: none;
    }

    .sx-row {
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px 12px;
    }

    .sx-state {
        grid-column: 2;
        grid-row: 1;
        align-items: flex-end;
    }

    .sx-pay {
        grid-column: 1 / -1;
    }

    /* No money yet (a request): nothing to show, not even the dash of the wide table. */
    .sx-pay:has(.sx-pay__none) {
        display: none;
    }

    .sx-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 599.98px) {
    .sx-money {
        grid-template-columns: 1fr 1fr;
    }

    .sx-money__progress {
        grid-column: 1 / -1;
    }

    .sx-search {
        flex-basis: 100%;
    }
}
</style>

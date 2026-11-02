<template>
    <Head :title="$t('teach.students.title', { title: item.title })" />

    <PageIntro
        :title="$t('teach.students.title', { title: item.title })"
        :breadcrumbs="[
            { title: $t('nav.home'), href: route('teach.dashboard') },
            { title: $t('teach.nav.classes'), href: route('teach.classes.index') },
            { title: item.title, href: route('teach.classes.edit', item.id) },
            { title: $t('teach.students.open') },
        ]"
    >
        <template #actions>
        <span class="rq-badge" :class="item.seats && accepted.length > item.seats ? 'rq-badge--warning' : 'rq-badge--success'">
            {{ item.seats
                ? $t('teach.students.of_seats', { accepted: accepted.length, seats: item.seats })
                : $t('teach.students.accepted_count', { accepted: accepted.length }) }}
        </span>
        </template>
    </PageIntro>

    <p v-if="enrollments.length === 0" class="rq-card teach-empty">{{ $t('teach.students.empty') }}</p>

    <article v-for="row in enrollments" :key="row.id" class="rq-card student" :class="{ 'student--muted': !['requested', 'accepted'].includes(row.status) }">
        <div class="student__main">
            <h2 class="student__name">
                {{ row.student?.name }}
                <EnrollmentStatusBadge :status="row.status" for-instructor class="ms-2" />
            </h2>
            <p class="student__meta">
                <span dir="ltr">{{ row.student?.phone }}</span>
                <template v-if="row.status === 'accepted'">
                    · {{ $t('teach.students.accepted_on', { date: formatDate(row.accepted_at) }) }}
                    · {{ $t('teach.students.fee', { fee: feeText(row.fee) }) }}
                </template>
            </p>
            <p v-if="row.status === 'accepted' || Number(row.totals.paid) > 0" class="student__money">
                {{ $t('teach.students.paid_remaining', { paid: row.totals.paid, remaining: row.totals.remaining }) }}
            </p>
            <p v-if="row.note" class="student__note">{{ row.note }}</p>
            <p v-if="row.decision_reason" class="student__note">{{ row.decision_reason }}</p>
        </div>

        <div class="student__actions">
            <WhatsAppButton :href="row.student?.whatsapp" />
            <template v-if="row.status === 'requested'">
                <v-btn class="rq-btn rq-btn--primary" @click="post('accept', row)">{{ $t('teach.requests.accept') }}</v-btn>
                <v-btn class="rq-btn" variant="outlined" color="error" @click="openReason('reject', row)">{{ $t('teach.requests.reject') }}</v-btn>
            </template>
            <Link
                v-if="row.status === 'accepted' || Number(row.totals.paid) > 0"
                :href="route('teach.enrollments.payments', row.id)"
                class="text-decoration-none"
            >
                <v-btn class="rq-btn rq-btn--primary">
                    <v-icon icon="tabler:cash" start />
                    {{ $t('teach.students.payments') }}
                </v-btn>
            </Link>
            <template v-if="row.status === 'accepted'">
                <v-btn class="rq-btn" variant="outlined" color="primary" @click="post('reset-link', row)">
                    <v-icon icon="tabler:key" start />
                    {{ $t('teach.students.reset_link') }}
                </v-btn>
                <v-btn class="rq-btn" variant="text" color="error" @click="openReason('remove', row)">
                    {{ $t('teach.students.remove') }}
                </v-btn>
            </template>
        </div>
    </article>

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
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import EnrollmentStatusBadge from '@/Components/EnrollmentStatusBadge.vue';
import ReasonDialog from '@/Components/ReasonDialog.vue';
import ResetLinkDialog from '@/Components/ResetLinkDialog.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';
import { formatPrice } from '@/utils/classFormat';
import { formatDate } from '@/utils/dates';

const props = defineProps({
    item: { type: Object, required: true },
    enrollments: { type: Array, default: () => [] },
    resetLink: { type: Object, default: null },
});

const { t } = useI18n();
const accepted = computed(() => props.enrollments.filter((row) => row.status === 'accepted'));

const reasonOpen = ref(false);
const reasonAction = ref('reject');
const reasonRow = ref(null);

function feeText(fee) {
    return formatPrice({ price: fee ?? '0', currency: 'LYD' }, t);
}

function post(action, row) {
    router.post(route(`teach.enrollments.${action}`, row.id), {}, { preserveScroll: true });
}

function openReason(action, row) {
    reasonAction.value = action;
    reasonRow.value = row;
    reasonOpen.value = true;
}
</script>

<style scoped src="../teach.css"></style>

<style scoped>
.students__back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
    color: var(--rq-text-link);
    font-size: 14px;
    text-decoration: none;
}

.student {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: space-between;
    padding: 18px 20px;
    margin-bottom: 10px;
    border-radius: var(--rq-radius-card);
}

.student--muted {
    opacity: 0.7;
}

.student__main {
    flex: 1 1 300px;
    min-width: 0;
}

.student__name {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-heading);
}

.student__meta {
    margin: 4px 0 0;
    font-size: 14px;
    color: var(--rq-text-muted);
}

.student__money {
    margin: 4px 0 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--rq-text-number);
}

.student__note {
    margin: 8px 0 0;
    padding: 6px 10px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-size: 14px;
    white-space: pre-line;
}

.student__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 8px;
}
</style>

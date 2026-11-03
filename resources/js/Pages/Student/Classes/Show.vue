<template>
    <Head :title="cls.title" />

    <div class="student-page">
        <PageIntro :title="cls.title" :description="$t('student.classes.requested_on', { date: formatDate(enrollment.requested_at) })">
            <template #title>
                {{ cls.title }}
                <EnrollmentStatusBadge :status="enrollment.status" class="ms-2 align-middle" />
            </template>
        </PageIntro>

        <div class="detail">
            <article class="rq-card detail__main">
                <div class="d-flex flex-wrap align-center ga-2">
                    <span class="rq-badge">{{ $t(`class.type.${cls.type}`) }}</span>
                </div>

                <v-alert v-if="cls.status === 'canceled'" type="warning" variant="tonal" class="my-4" rounded="lg">
                    {{ $t('student.classes.class_canceled') }}
                    <div v-if="cls.cancel_reason" class="mt-1">{{ cls.cancel_reason }}</div>
                </v-alert>
                <v-alert v-else-if="cls.status === 'suspended'" type="warning" variant="tonal" class="my-4" rounded="lg">
                    {{ $t('student.classes.class_suspended') }}
                </v-alert>
                <p v-else-if="enrollment.decision_reason && ['rejected', 'canceled'].includes(enrollment.status)" class="detail__reason">
                    {{ $t('student.classes.reason', { reason: enrollment.decision_reason }) }}
                </p>

                <!-- The instructor moved the dates, hours or place in the last few days (accepted students only). -->
                <v-alert
                    v-if="cls.schedule_changed_at && cls.status !== 'canceled'"
                    type="info"
                    variant="tonal"
                    class="my-4"
                    rounded="lg"
                    icon="tabler:calendar-event"
                >
                    {{ $t('student.classes.schedule_changed', { date: formatDate(cls.schedule_changed_at) }) }}
                </v-alert>

                <!-- The meeting link: only an accepted student of a running online class gets it from the server. -->
                <section v-if="enrollment.status === 'accepted' && cls.type === 'online'" class="detail__meeting">
                    <h2 class="detail__subtitle">
                        {{ $t('student.classes.meeting') }}
                        <span v-if="cls.meeting_url_new" class="detail__new">{{ $t('student.classes.meeting_updated') }}</span>
                    </h2>
                    <p v-if="cls.meeting_url_new" class="detail__muted mb-3">
                        {{ $t('student.classes.meeting_updated_on', { date: formatDate(cls.meeting_url_changed_at) }) }}
                    </p>
                    <v-btn
                        v-if="cls.meeting_url"
                        class="rq-btn rq-btn--primary"
                        :href="cls.meeting_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <v-icon icon="tabler:video" start />
                        {{ $t('student.classes.join_meeting') }}
                    </v-btn>
                    <p v-else class="detail__muted">{{ $t('student.classes.meeting_missing') }}</p>
                </section>

                <!-- Plain text, kept as the instructor typed it: shown as text (never as HTML), line breaks kept. -->
                <p v-if="cls.description" class="detail__description">{{ cls.description }}</p>

                <p v-if="enrollment.note" class="detail__note">
                    <strong>{{ $t('student.classes.your_note') }}:</strong> {{ enrollment.note }}
                </p>

                <section v-if="enrollment.fee !== null || payments.length" class="detail__payments">
                    <h2 class="detail__subtitle">{{ $t('student.classes.payments') }}</h2>
                    <MoneyTotals :totals="totals" class="mb-3" />
                    <PaymentList :payments="payments" :empty-text="$t('student.classes.no_payments')" />
                </section>
            </article>

            <aside class="rq-card detail__side">
                <dl class="detail__facts">
                    <div>
                        <dt>{{ $t('student.classes.instructor') }}</dt>
                        <dd>
                            <a v-if="instructor.url" :href="instructor.url">{{ instructor.display_name }}</a>
                            <template v-else>{{ instructor.display_name }}</template>
                        </dd>
                    </div>
                    <div>
                        <dt>{{ $t('public.schedule') }}</dt>
                        <dd>
                            {{ formatPeriod(cls, t) }}
                            <template v-if="schedule"><br>{{ schedule }}</template>
                        </dd>
                    </div>
                    <div>
                        <dt>{{ $t('public.place') }}</dt>
                        <dd>
                            <template v-if="cls.type === 'in_person'">
                                <template v-if="cls.location">{{ cls.location }}<br></template>
                                <a v-if="cls.map_url" :href="cls.map_url" target="_blank" rel="noopener noreferrer">{{ $t('public.map') }}</a>
                            </template>
                            <template v-else>{{ $t('class.type.online') }}</template>
                        </dd>
                    </div>
                    <div v-if="enrollment.status === 'accepted'">
                        <dt>{{ $t('student.classes.fee') }}</dt>
                        <dd class="detail__price rq-number">{{ formatPrice({ price: enrollment.fee ?? cls.price, currency: cls.currency }, t) }}</dd>
                    </div>
                </dl>

                <div class="d-flex flex-column ga-2">
                    <WhatsAppButton :href="instructor.whatsapp">{{ $t('student.classes.contact') }}</WhatsAppButton>
                    <v-btn v-if="cls.url" class="rq-btn" variant="outlined" color="primary" :href="cls.url">
                        {{ $t('student.classes.class_page') }}
                    </v-btn>
                    <v-btn
                        v-if="enrollment.status === 'requested'"
                        class="rq-btn"
                        variant="text"
                        color="error"
                        @click="withdraw($t('student.classes.withdraw_confirm'))"
                    >
                        {{ $t('student.classes.withdraw') }}
                    </v-btn>
                    <v-btn
                        v-if="enrollment.status === 'accepted'"
                        class="rq-btn"
                        variant="text"
                        color="error"
                        @click="withdraw($t('student.classes.leave_confirm'))"
                    >
                        {{ $t('student.classes.leave') }}
                    </v-btn>
                </div>
            </aside>
        </div>
    </div>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import EnrollmentStatusBadge from '@/Components/EnrollmentStatusBadge.vue';
import MoneyTotals from '@/Components/MoneyTotals.vue';
import PaymentList from '@/Components/PaymentList.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';
import { formatPeriod, formatPrice, formatSchedule } from '@/utils/classFormat';
import { formatDate } from '@/utils/dates';

const props = defineProps({
    enrollment: { type: Object, required: true },
    totals: { type: Object, required: true },
    payments: { type: Array, default: () => [] },
});

const { t } = useI18n();
const cls = computed(() => props.enrollment.class);
const instructor = computed(() => props.enrollment.instructor);
const schedule = computed(() => formatSchedule(cls.value, t));

function withdraw(question) {
    if (window.confirm(question)) {
        router.post(route('student.classes.withdraw', props.enrollment.id), {}, { preserveScroll: true });
    }
}
</script>

<style scoped src="./student.css"></style>

<style scoped>
.detail__back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 16px;
    color: var(--rq-text-link);
    font-size: 14px;
    text-decoration: none;
}

.detail {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 16px;
    align-items: start;
}

@media (max-width: 860px) {
    .detail {
        grid-template-columns: minmax(0, 1fr);
    }
}

.detail__main,
.detail__side {
    padding: 24px;
    border-radius: var(--rq-radius-card);
}

.detail__title {
    margin: 0 0 4px;
    font-size: clamp(22px, 3vw, 28px);
    font-weight: 700;
    color: var(--rq-text-heading);
}

.detail__subtitle {
    margin: 0 0 10px;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    color: var(--rq-text-heading);
}

.detail__new {
    display: inline-block;
    margin-inline-start: 8px;
    padding: 2px 10px;
    border-radius: var(--rq-radius-pill);
    background: var(--rq-success-tint);
    font-family: var(--rq-font-body, inherit);
    font-size: 12px;
    font-weight: 600;
    color: var(--rq-success);
    vertical-align: middle;
}

.detail__muted {
    margin: 0;
    font-size: 13px;
    color: var(--rq-text-subtle);
}

.detail__reason,
.detail__note {
    margin: 16px 0 0;
    padding: 8px 12px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-size: 14px;
}

.detail__meeting {
    margin: 20px 0;
    padding: 16px;
    border-radius: var(--rq-radius-md);
    background: var(--rq-surface-hover);
}

.detail__payments {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--rq-line-soft);
}

.detail__description {
    margin: 20px 0 0;
    line-height: var(--rq-lh-body);
    white-space: pre-line;
    overflow-wrap: anywhere;
}

.detail__facts {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin: 0 0 20px;
}

.detail__facts dt {
    font-size: var(--rq-text-caption);
    color: var(--rq-text-subtle);
}

.detail__facts dd {
    margin: 2px 0 0;
}

.detail__facts a {
    color: var(--rq-text-link);
}

.detail__price {
    font-family: var(--rq-font-display);
    font-size: 20px;
    font-weight: 700;
    color: var(--rq-text-number);
}
</style>

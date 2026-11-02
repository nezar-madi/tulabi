<template>
    <Head :title="$t('teach.money.title')" />

    <div class="money-page">
        <PageIntro :title="$t('teach.money.title')" :description="$t('teach.money.lead')">
            <template #actions>
                <v-btn
                    class="text-none"
                    variant="flat"
                    color="primary"
                    prepend-icon="tabler:plus"
                    :disabled="payable.length === 0"
                    @click="openRecord"
                >
                    {{ $t('teach.money.record') }}
                </v-btn>
            </template>

            <PageIntroStats :items="stats" />
        </PageIntro>

        <!-- What the totals are made of, by payment method -->
        <div v-if="totals.by_method.length > 1" class="money-methods">
            <span v-for="item in totals.by_method" :key="item.method" class="money-methods__chip">
                {{ $t(`money.methods.${item.method}`) }}:
                <strong class="rq-number">{{ $t('money.amount', { amount: item.total }) }}</strong>
                <small>({{ item.count }})</small>
            </span>
        </div>

        <!-- Filters: they live in the address, so a view can be reloaded or shared as it is -->
        <section class="rq-card money-filters">
            <div class="money-filters__row">
                <v-select
                    :model-value="period"
                    :items="periodItems"
                    :label="$t('teach.money.period')"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    class="money-filters__field"
                    @update:model-value="choosePeriod"
                />
                <v-select
                    :model-value="filters.course"
                    :items="courseItems"
                    item-title="title"
                    item-value="id"
                    :label="$t('teach.money.course')"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    class="money-filters__field"
                    @update:model-value="apply({ course: $event })"
                />
                <v-select
                    :model-value="filters.method"
                    :items="methodItems"
                    :label="$t('teach.payments.method')"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    class="money-filters__field money-filters__field--narrow"
                    @update:model-value="apply({ method: $event })"
                />
                <v-text-field
                    v-model="search"
                    :label="$t('teach.money.search')"
                    prepend-inner-icon="tabler:search"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    clearable
                    class="money-filters__field money-filters__field--wide"
                    @update:model-value="searchSoon"
                />
            </div>

            <div v-if="period === 'custom'" class="money-filters__row mt-3">
                <FormPlainDatePicker
                    :model-value="filters.from || ''"
                    :label="$t('teach.money.from')"
                    :max="filters.to || today"
                    class="money-filters__field"
                    @update:model-value="apply({ from: $event || null })"
                />
                <FormPlainDatePicker
                    :model-value="filters.to || ''"
                    :label="$t('teach.money.to')"
                    :min="filters.from || undefined"
                    :max="today"
                    class="money-filters__field"
                    @update:model-value="apply({ to: $event || null })"
                />
            </div>
        </section>

        <div class="teach-tabs money-tabs">
            <button
                v-for="tab in statusTabs"
                :key="tab"
                type="button"
                class="teach-tabs__tab"
                :class="{ 'teach-tabs__tab--active': filters.status === tab }"
                @click="apply({ status: tab })"
            >
                {{ $t(`teach.money.status.${tab}`) }}
            </button>
        </div>

        <!-- The payments -->
        <section class="rq-card money-list">
            <p v-if="payments.data.length === 0" class="money-list__empty">
                {{ hasFilters ? $t('teach.money.empty_filtered') : $t('teach.money.empty') }}
            </p>

            <div v-else class="money-table">
                <div class="money-table__head">
                    <span>{{ $t('teach.money.columns.date') }}</span>
                    <span>{{ $t('teach.money.columns.student') }}</span>
                    <span>{{ $t('teach.money.columns.amount') }}</span>
                    <span>{{ $t('teach.money.columns.receipt') }}</span>
                    <span />
                </div>
                <div
                    v-for="payment in payments.data"
                    :key="payment.id"
                    class="money-table__row"
                    :class="{ 'money-table__row--void': payment.voided }"
                >
                    <span class="money-table__date">
                        {{ formatDay(payment.paid_on) }}
                        <small>{{ $t(`money.methods.${payment.method}`) }}</small>
                    </span>
                    <span class="money-table__who">
                        <strong>{{ payment.student?.name || '—' }}</strong>
                        <small>{{ payment.class?.title }}</small>
                    </span>
                    <span class="money-table__amount">
                        <strong class="rq-number">{{ $t('money.amount', { amount: payment.amount }) }}</strong>
                        <span v-if="payment.voided" class="rq-badge rq-badge--danger">{{ $t('money.voided') }}</span>
                    </span>
                    <span class="money-table__receipt" dir="ltr">{{ payment.receipt_number }}</span>
                    <span class="money-table__actions">
                        <v-btn
                            class="rq-btn"
                            variant="text"
                            color="primary"
                            size="small"
                            :href="payment.receipt_url"
                            target="_blank"
                            rel="noopener"
                            :aria-label="$t('money.receipt')"
                        >
                            <v-icon icon="tabler:file-invoice" start />
                            {{ $t('money.receipt') }}
                        </v-btn>
                        <v-menu location="bottom end">
                            <template #activator="{ props: menuProps }">
                                <v-btn v-bind="menuProps" icon variant="text" size="small" :aria-label="$t('teach.money.more')">
                                    <v-icon icon="tabler:dots-vertical" />
                                </v-btn>
                            </template>
                            <v-list density="compact">
                                <v-list-item
                                    v-if="payment.enrollment_id"
                                    :title="$t('teach.money.student_payments')"
                                    @click="router.visit(route('teach.enrollments.payments', payment.enrollment_id))"
                                />
                                <v-list-item v-if="!payment.voided" :title="$t('teach.payments.edit')" @click="openEdit(payment)" />
                                <v-list-item
                                    v-if="!payment.voided"
                                    :title="$t('teach.payments.void')"
                                    base-color="error"
                                    @click="openVoid(payment)"
                                />
                            </v-list>
                        </v-menu>
                    </span>
                    <span v-if="payment.voided && payment.void_reason" class="money-table__note">
                        {{ $t('money.void_reason', { reason: payment.void_reason }) }}
                    </span>
                    <span v-else-if="payment.notes" class="money-table__note">{{ payment.notes }}</span>
                </div>
            </div>
        </section>

        <Pager :paginator="payments" />
    </div>

    <!-- Record a payment for any student who still owes -->
    <v-dialog v-model="recordOpen" max-width="520" scrollable>
        <v-card class="app-form-dialog">
            <v-card-text class="app-form-dialog__body pt-6 pb-2">
                <h2 class="rq-dialog__title mb-4">{{ $t('teach.money.record') }}</h2>
                <FormPlainSelect
                    v-model="recordEnrollment"
                    :label="$t('teach.money.who')"
                    :items="payableItems"
                    item-title="title"
                    item-value="id"
                    searchable
                    required
                    :error-messages="recordForm.errors.enrollment"
                    class="mb-3"
                />
                <p v-if="chosen" class="teach-hint mt-0">{{ $t('teach.money.remaining', { amount: chosen.remaining }) }}</p>
                <FormPlainTextField
                    v-model="recordForm.amount"
                    :label="`${$t('teach.payments.amount')} (د.ل)`"
                    hide-placeholder
                    required
                    :field-attrs="{ name: 'amount', inputmode: 'decimal', dir: 'ltr' }"
                    :error-messages="recordForm.errors.amount"
                    class="mb-3"
                />
                <FormPlainDatePicker
                    v-model="recordForm.paid_at"
                    :label="$t('teach.payments.paid_at')"
                    required
                    :max="today"
                    :error-messages="recordForm.errors.paid_at"
                    class="mb-3"
                />
                <p class="form-plain-field__label font-weight-medium mb-2">{{ $t('teach.payments.method') }}</p>
                <v-btn-toggle v-model="recordForm.method" mandatory class="mb-4">
                    <v-btn v-for="method in methods" :key="method" :value="method">{{ $t(`money.methods.${method}`) }}</v-btn>
                </v-btn-toggle>
                <FormPlainTextarea
                    v-model="recordForm.notes"
                    :label="$t('teach.payments.notes')"
                    :rows="2"
                    :maxlength="500"
                    :error-messages="recordForm.errors.notes"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="recordOpen = false">{{ $t('common.cancel') }}</v-btn>
                <v-btn class="rq-btn rq-btn--primary" :loading="recordForm.processing" :disabled="!recordEnrollment" @click="record">
                    {{ $t('teach.payments.record') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="editOpen" max-width="480" scrollable>
        <v-card v-if="editing" class="app-form-dialog">
            <v-card-text class="app-form-dialog__body pt-6 pb-2">
                <h2 class="rq-dialog__title mb-2">{{ $t('teach.payments.edit_title', { number: editing.receipt_number }) }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ $t('teach.payments.edit_note') }}</p>
                <v-btn-toggle v-model="editForm.method" mandatory class="mb-4">
                    <v-btn v-for="method in methods" :key="method" :value="method">{{ $t(`money.methods.${method}`) }}</v-btn>
                </v-btn-toggle>
                <FormPlainTextarea
                    v-model="editForm.notes"
                    :label="$t('teach.payments.notes')"
                    :rows="2"
                    :maxlength="500"
                    :error-messages="editForm.errors.notes"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="editOpen = false">{{ $t('common.cancel') }}</v-btn>
                <v-btn class="rq-btn rq-btn--primary" :loading="editForm.processing" @click="saveEdit">{{ $t('teach.payments.save') }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="voidOpen" max-width="480" scrollable>
        <v-card v-if="voiding" class="app-form-dialog">
            <v-card-text class="app-form-dialog__body pt-6 pb-2">
                <h2 class="rq-dialog__title mb-2">{{ $t('teach.payments.void_title', { number: voiding.receipt_number }) }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ $t('teach.payments.void_text') }}</p>
                <FormPlainTextarea
                    v-model="voidForm.reason"
                    :label="$t('teach.payments.void_reason')"
                    required
                    :rows="2"
                    :maxlength="500"
                    :error-messages="voidForm.errors.reason"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="voidOpen = false">{{ $t('common.close') }}</v-btn>
                <v-btn class="rq-btn" variant="flat" color="error" :loading="voidForm.processing" @click="saveVoid">
                    {{ $t('teach.payments.void_confirm') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Pager from '@/Components/Pager.vue';
import PageIntro from '@/Components/PageIntro.vue';
import PageIntroStats from '@/Components/PageIntroStats.vue';
import FormPlainDatePicker from '@/Components/formFields/FormPlainDatePicker.vue';
import FormPlainSelect from '@/Components/formFields/FormPlainSelect.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';
import { formatDay } from '@/utils/classFormat';

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, required: true },
    courses: { type: Array, default: () => [] },
    methods: { type: Array, default: () => [] },
    totals: { type: Object, required: true },
    payable: { type: Array, default: () => [] },
    today: { type: String, required: true },
    monthStart: { type: String, required: true },
});

const { t } = useI18n();

const stats = computed(() => [
    {
        key: 'collected',
        label: t('teach.money.stat_collected'),
        value: t('money.amount', { amount: props.totals.collected }),
        icon: 'tabler:cash',
        tone: 'success',
    },
    { key: 'count', label: t('teach.money.stat_count'), value: props.totals.count, icon: 'tabler:receipt', tone: 'sky' },
    { key: 'voided', label: t('teach.money.stat_voided'), value: props.totals.voided, icon: 'tabler:x', tone: 'rose' },
]);

const statusTabs = ['valid', 'voided', 'all'];

const hasFilters = computed(() => Boolean(props.filters.from || props.filters.to || props.filters.course
    || props.filters.method || props.filters.q));

const courseItems = computed(() => [{ id: null, title: t('teach.money.all_courses') }, ...props.courses]);
const methodItems = computed(() => [
    { value: null, title: t('teach.money.all_methods') },
    ...props.methods.map((method) => ({ value: method, title: t(`money.methods.${method}`) })),
]);

// Period presets, worked out from the server's "today" and month start (Libya's calendar).
function shiftMonth(date, months) {
    const [y, m] = date.split('-').map(Number);
    const d = new Date(Date.UTC(y, m - 1 + months, 1));

    return d.toISOString().slice(0, 10);
}

const presets = computed(() => {
    const [year, month] = props.monthStart.split('-').map(Number);
    const lastMonthStart = shiftMonth(props.monthStart, -1);
    // Day 0 of this month is the last day of the one before.
    const lastMonthEnd = new Date(Date.UTC(year, month - 1, 0)).toISOString().slice(0, 10);

    return {
        all: { from: null, to: null },
        month: { from: props.monthStart, to: props.today },
        last_month: { from: lastMonthStart, to: lastMonthEnd },
        year: { from: `${props.today.slice(0, 4)}-01-01`, to: props.today },
    };
});

const customPicked = ref(false);

const period = computed(() => {
    if (customPicked.value) {
        return 'custom';
    }

    const match = Object.entries(presets.value)
        .find(([, range]) => range.from === (props.filters.from || null) && range.to === (props.filters.to || null));

    return match ? match[0] : 'custom';
});

const periodItems = computed(() => ['all', 'month', 'last_month', 'year', 'custom']
    .map((value) => ({ value, title: t(`teach.money.periods.${value}`) })));

function choosePeriod(value) {
    if (value === 'custom') {
        customPicked.value = true;

        return;
    }

    customPicked.value = false;
    apply(presets.value[value]);
}

function apply(change) {
    const next = { ...props.filters, ...change };
    const query = Object.fromEntries(Object.entries({
        from: next.from,
        to: next.to,
        course: next.course,
        method: next.method,
        status: next.status === 'valid' ? null : next.status,
        q: next.q,
    }).filter(([, value]) => value !== null && value !== undefined && value !== ''));

    router.get(route('teach.payments.index'), query, { preserveState: true, preserveScroll: true, replace: true });
}

const search = ref(props.filters.q);
let timer = null;

function searchSoon(value) {
    clearTimeout(timer);
    timer = setTimeout(() => apply({ q: value || null }), 350);
}

onBeforeUnmount(() => clearTimeout(timer));

// Record a payment
const recordOpen = ref(false);
const recordEnrollment = ref(null);
const recordForm = useForm({ amount: '', paid_at: props.today, method: 'cash', notes: '' });
const payableItems = computed(() => props.payable.map((item) => ({
    id: item.id,
    title: `${item.name} — ${item.class}`,
})));
const chosen = computed(() => props.payable.find((item) => item.id === recordEnrollment.value) || null);

function openRecord() {
    recordForm.reset();
    recordForm.clearErrors();
    recordForm.paid_at = props.today;
    recordEnrollment.value = null;
    recordOpen.value = true;
}

function record() {
    recordForm.post(route('teach.enrollments.payments.store', recordEnrollment.value), {
        preserveScroll: true,
        onSuccess: () => {
            recordOpen.value = false;
        },
    });
}

// Edit the method and notes; void with a reason
const editOpen = ref(false);
const editing = ref(null);
const editForm = useForm({ method: 'cash', notes: '' });

function openEdit(payment) {
    editing.value = payment;
    editForm.method = payment.method;
    editForm.notes = payment.notes || '';
    editForm.clearErrors();
    editOpen.value = true;
}

function saveEdit() {
    editForm.put(route('teach.payments.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

const voidOpen = ref(false);
const voiding = ref(null);
const voidForm = useForm({ reason: '' });

function openVoid(payment) {
    voiding.value = payment;
    voidForm.reset();
    voidForm.clearErrors();
    voidOpen.value = true;
}

function saveVoid() {
    voidForm.post(route('teach.payments.void', voiding.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            voidOpen.value = false;
        },
    });
}
</script>

<style scoped src="./teach.css"></style>

<style scoped>
.money-page {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-4);
}

.money-methods {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.money-methods__chip {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    padding: 6px 12px;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface);
    font-size: 13px;
    color: var(--rq-text-muted);
}

.money-methods__chip strong {
    color: var(--rq-text-heading);
}

.money-filters {
    padding: 16px;
    border-radius: var(--rq-radius-card);
}

.money-filters__row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.money-filters__field {
    flex: 1 1 180px;
}

.money-filters__field--narrow {
    flex-basis: 140px;
}

.money-filters__field--wide {
    flex-basis: 240px;
}

.money-tabs {
    margin-bottom: 0;
}

.money-tabs .teach-tabs__tab {
    border-top: 0;
    border-inline: 0;
    background: none;
    cursor: pointer;
}

.money-list {
    overflow: hidden;
    border-radius: var(--rq-radius-card);
}

.money-list__empty {
    margin: 0;
    padding: 32px 20px;
    text-align: center;
    color: var(--rq-text-muted);
}

.money-table__head,
.money-table__row {
    display: grid;
    grid-template-columns: 120px minmax(0, 1.6fr) minmax(0, 1fr) 150px auto;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
}

.money-table__head {
    border-bottom: 1px solid var(--rq-line);
    background: var(--rq-surface-chrome);
    font-size: 12px;
    font-weight: 600;
    color: var(--rq-text-muted);
}

.money-table__row {
    border-top: 1px solid var(--rq-line-soft);
}

.money-table__row:first-of-type {
    border-top: 0;
}

.money-table__row:hover {
    background: var(--rq-surface-hover);
}

.money-table__row--void .money-table__amount strong {
    color: var(--rq-text-subtle);
    text-decoration: line-through;
}

.money-table__date,
.money-table__who {
    display: flex;
    flex-direction: column;
    min-width: 0;
    font-size: 14px;
}

.money-table__date small,
.money-table__who small {
    overflow: hidden;
    font-size: 12px;
    color: var(--rq-text-muted);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.money-table__who strong {
    overflow: hidden;
    color: var(--rq-text-heading);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.money-table__amount {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.money-table__amount strong {
    font-family: var(--rq-font-display);
    color: var(--rq-text-number);
}

.money-table__receipt {
    font-size: 12px;
    color: var(--rq-text-muted);
    text-align: end;
}

.money-table__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.money-table__note {
    grid-column: 2 / -1;
    margin-top: -6px;
    font-size: 12px;
    color: var(--rq-text-muted);
}

@media (max-width: 759.98px) {
    .money-table__head {
        display: none;
    }

    .money-table__row {
        grid-template-columns: minmax(0, 1fr) auto;
        padding-inline: 16px;
    }

    .money-table__date {
        grid-column: 1 / -1;
        flex-direction: row;
        gap: 8px;
        color: var(--rq-text-muted);
    }

    .money-table__receipt {
        text-align: start;
    }

    .money-table__note {
        grid-column: 1 / -1;
    }
}
</style>

<template>
    <Head :title="$t('teach.payments.title', { name: enrollment.student?.name || '' })" />

    <!--
        One focus (emphasis through contrast): what the student still owes and the button that
        records a payment. Everything else (the totals behind it, the history, WhatsApp, the row
        actions) is plain text in neutral colours so it does not compete.
    -->
    <PageIntro
        :breadcrumbs="[
            { title: $t('nav.home'), href: route('teach.dashboard') },
            { title: $t('teach.nav.classes'), href: route('teach.classes.index') },
            { title: enrollment.class.title, href: route('teach.classes.students', enrollment.class.id) },
            { title: $t('teach.students.payments') },
        ]"
        :title="$t('teach.payments.title', { name: enrollment.student?.name || '' })"
    >
        <template v-if="enrollment.student?.whatsapp" #actions>
            <v-btn
                class="rq-btn pay-quiet-btn"
                variant="text"
                :href="enrollment.student.whatsapp"
                target="_blank"
                rel="noopener noreferrer"
            >
                <v-icon icon="tabler:brand-whatsapp" start class="pay-quiet-btn__wa" />
                {{ $t('common.whatsapp') }}
            </v-btn>
        </template>
    </PageIntro>

    <div class="payments">
        <section class="rq-card pay-focus">
            <!-- The balance: the one big number on the page -->
            <div class="pay-balance" :class="{ 'pay-balance--settled': settled }">
                <span class="pay-balance__label">{{ settled ? $t('teach.payments.settled') : $t('money.remaining') }}</span>
                <strong class="pay-balance__amount rq-number">
                    <v-icon v-if="settled" icon="tabler:circle-check" size="20" class="me-1" />
                    {{ $t('money.amount', { amount: totals.remaining }) }}
                </strong>
                <template v-if="totals.fee !== null">
                    <div class="pay-balance__bar" role="presentation">
                        <span :style="{ width: `${paidShare}%` }" />
                    </div>
                    <span class="pay-balance__of">{{ $t('teach.payments.paid_of', { paid: totals.paid, fee: totals.fee }) }}</span>
                </template>
            </div>

            <p v-if="!canPay" class="pay-focus__note">
                {{ enrollment.status === 'accepted' ? $t('teach.payments.paid_in_full') : $t('teach.payments.not_member') }}
            </p>

            <form v-else class="pay-form" @submit.prevent="record">
                <h2 class="pay-focus__title">{{ $t('teach.payments.record_title') }}</h2>
                <FormPlainTextField
                    v-model="form.amount"
                    :label="`${$t('teach.payments.amount')} (د.ل)`"
                    hide-placeholder
                    :field-attrs="{ name: 'amount', inputmode: 'decimal', dir: 'ltr' }"
                    :error-messages="form.errors.amount"
                    class="mb-3"
                />
                <FormPlainTextField
                    v-model="form.paid_at"
                    :label="$t('teach.payments.paid_at')"
                    type="date"
                    :field-attrs="{ name: 'paid_at', max: today }"
                    :error-messages="form.errors.paid_at"
                    class="mb-3"
                />
                <p class="form-plain-field__label font-weight-medium mb-2">{{ $t('teach.payments.method') }}</p>
                <v-btn-toggle v-model="form.method" mandatory class="mb-4">
                    <v-btn v-for="method in methods" :key="method" :value="method">{{ $t(`money.methods.${method}`) }}</v-btn>
                </v-btn-toggle>
                <FormPlainTextarea
                    v-model="form.notes"
                    :label="$t('teach.payments.notes')"
                    :rows="2"
                    :maxlength="500"
                    :error-messages="form.errors.notes"
                    class="mb-4"
                />
                <v-btn type="submit" class="rq-btn rq-btn--primary" size="large" block :loading="form.processing">
                    <v-icon icon="tabler:cash" start />
                    {{ $t('teach.payments.record') }}
                </v-btn>
            </form>
        </section>

        <section class="pay-history">
            <h2 class="pay-history__title">
                {{ $t('teach.payments.list_title') }}
                <span v-if="payments.length" class="pay-history__count rq-number">{{ payments.length }}</span>
            </h2>
            <PaymentList :payments="payments" :empty-text="$t('teach.payments.empty')" quiet>
                <template #actions="{ payment }">
                    <v-menu v-if="!payment.voided" location="bottom end">
                        <template #activator="{ props: menu }">
                            <v-btn
                                v-bind="menu"
                                class="pay-quiet-btn"
                                variant="text"
                                size="small"
                                icon="tabler:dots-vertical"
                                :aria-label="$t('teach.payments.actions')"
                            />
                        </template>
                        <v-list density="compact" class="py-1">
                            <v-list-item class="pay-menu-item" @click="openEdit(payment)">
                                <template #prepend><v-icon icon="tabler:pencil" size="18" /></template>
                                <v-list-item-title>{{ $t('teach.payments.edit') }}</v-list-item-title>
                            </v-list-item>
                            <v-list-item class="pay-menu-item text-error" @click="openVoid(payment)">
                                <template #prepend><v-icon icon="tabler:ban" size="18" /></template>
                                <v-list-item-title>{{ $t('teach.payments.void') }}</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                </template>
            </PaymentList>
        </section>
    </div>

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
                <v-btn class="rq-btn" variant="text" @click="voidOpen = false">{{ $t('common.cancel') }}</v-btn>
                <v-btn class="rq-btn" variant="flat" color="error" :loading="voidForm.processing" @click="saveVoid">
                    {{ $t('teach.payments.void_confirm') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PaymentList from '@/Components/PaymentList.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    enrollment: { type: Object, required: true },
    totals: { type: Object, required: true },
    canPay: { type: Boolean, default: false },
    payments: { type: Array, default: () => [] },
    methods: { type: Array, default: () => ['cash', 'transfer', 'other'] },
    today: { type: String, required: true },
});

// Paid in full: the balance turns green and says so.
const settled = computed(() => props.totals.fee !== null && Number(props.totals.remaining) <= 0);
const paidShare = computed(() => {
    const fee = Number(props.totals.fee);
    return fee > 0 ? Math.min(100, Math.round((Number(props.totals.paid) / fee) * 100)) : 0;
});

const form = useForm({
    amount: props.totals.remaining !== '0' ? props.totals.remaining : '',
    paid_at: props.today,
    method: 'cash',
    notes: '',
});

function record() {
    form.post(route('teach.enrollments.payments.store', props.enrollment.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('notes');
            form.amount = props.totals.remaining !== '0' ? props.totals.remaining : '';
        },
    });
}

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
.payments {
    display: grid;
    grid-template-columns: minmax(0, 380px) minmax(0, 1fr);
    gap: 32px;
    align-items: start;
}

@media (max-width: 820px) {
    .payments {
        grid-template-columns: minmax(0, 1fr);
        gap: 24px;
    }
}

/* ---------- The focus: balance + record form, the only card on the page ---------- */
.pay-focus {
    padding: 24px;
    border-radius: var(--rq-radius-card);
}

.pay-balance {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-bottom: 20px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--rq-line-soft);
}

.pay-balance__label {
    font-size: 13px;
    color: var(--rq-text-subtle);
}

.pay-balance__amount {
    display: flex;
    align-items: center;
    font-family: var(--rq-font-display);
    font-size: 18px;
    font-weight: 700;
    line-height: 1.15;
    color: var(--rq-text-number);
}

.pay-balance__bar {
    height: 6px;
    margin-top: 8px;
    border-radius: 3px;
    background: var(--rq-surface-chrome);
    overflow: hidden;
}

.pay-balance__bar > span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--rq-text-subtle);
}

.pay-balance__of {
    font-size: 13px;
    color: var(--rq-text-subtle);
}

.pay-balance--settled .pay-balance__amount,
.pay-balance--settled .pay-balance__label {
    color: var(--rq-success);
}

.pay-balance--settled .pay-balance__bar > span {
    background: var(--rq-success);
}

.pay-focus__title {
    margin: 0 0 14px;
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-body);
}

.pay-focus__note {
    margin: 0;
    font-size: 14px;
    color: var(--rq-text-muted);
}

/* ---------- The history: no card, no colour, it steps back ---------- */
.pay-history {
    padding-top: 8px;
}

.pay-history__title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px;
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-muted);
}

.pay-history__count {
    min-width: 22px;
    padding: 0 7px;
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface-chrome);
    font-size: 12px;
    line-height: 20px;
    text-align: center;
    color: var(--rq-text-subtle);
}

/* Neutral text buttons: WhatsApp keeps only its green icon, row menus stay grey */
.pay-quiet-btn {
    color: var(--rq-text-muted) !important;
}

.pay-quiet-btn__wa {
    color: #25d366;
}

.pay-menu-item {
    min-height: 34px !important;
}

.pay-menu-item :deep(.v-list-item-title) {
    font-size: 13px;
    font-weight: 500;
}
</style>

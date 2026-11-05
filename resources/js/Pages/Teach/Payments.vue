<template>
    <Head :title="$t('teach.payments.title', { name: enrollment.student?.name || '' })" />

    <PageIntro
        :title="$t('teach.payments.title', { name: enrollment.student?.name || '' })"
    >
        <template #actions>
            <WhatsAppButton :href="enrollment.student?.whatsapp" />
        </template>
    </PageIntro>

    <MoneyTotals :totals="totals" class="mb-4" />

    <div class="payments">
        <section class="rq-card teach-section">
            <h2 class="teach-section__title">{{ $t('teach.payments.record_title') }}</h2>

            <p v-if="!canPay" class="teach-hint mt-0">
                {{ enrollment.status === 'accepted' ? $t('teach.payments.paid_in_full') : $t('teach.payments.not_member') }}
            </p>

            <form v-else @submit.prevent="record">
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
                    class="mb-3"
                />
                <v-btn type="submit" class="rq-btn rq-btn--primary" size="large" :loading="form.processing">
                    <v-icon icon="tabler:cash" start />
                    {{ $t('teach.payments.record') }}
                </v-btn>
            </form>
        </section>

        <section class="rq-card teach-section">
            <h2 class="teach-section__title">{{ $t('teach.payments.list_title') }}</h2>
            <PaymentList :payments="payments" :empty-text="$t('teach.payments.empty')">
                <template #actions="{ payment }">
                    <template v-if="!payment.voided">
                        <v-btn class="rq-btn" variant="text" size="small" @click="openEdit(payment)">
                            {{ $t('teach.payments.edit') }}
                        </v-btn>
                        <v-btn class="rq-btn" variant="text" color="error" size="small" @click="openVoid(payment)">
                            {{ $t('teach.payments.void') }}
                        </v-btn>
                    </template>
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
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MoneyTotals from '@/Components/MoneyTotals.vue';
import PaymentList from '@/Components/PaymentList.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';
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
.payments__back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 12px;
    color: var(--rq-text-link);
    font-size: 14px;
    text-decoration: none;
}

.payments {
    display: grid;
    grid-template-columns: minmax(0, 360px) minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

@media (max-width: 820px) {
    .payments {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>

<template>
    <p v-if="payments.length === 0" class="payment-list__empty">{{ emptyText }}</p>
    <ul v-else class="payment-list" :class="{ 'payment-list--quiet': quiet }">
        <li v-for="payment in payments" :key="payment.id" class="payment-list__row" :class="{ 'payment-list__row--void': payment.voided }">
            <div class="payment-list__main">
                <strong class="payment-list__amount rq-number">{{ $t('money.amount', { amount: payment.amount }) }}</strong>
                <span v-if="payment.voided" class="rq-badge rq-badge--danger">{{ $t('money.voided') }}</span>
                <div class="payment-list__meta">
                    {{ formatDay(payment.paid_on) }} · {{ $t(`money.methods.${payment.method}`) }} ·
                    <span dir="ltr">{{ payment.receipt_number }}</span>
                    <template v-if="extra(payment)"> · {{ extra(payment) }}</template>
                </div>
                <div v-if="payment.notes" class="payment-list__meta">{{ payment.notes }}</div>
                <div v-if="payment.voided && payment.void_reason" class="payment-list__meta">
                    {{ $t('money.void_reason', { reason: payment.void_reason }) }}
                </div>
            </div>
            <div class="payment-list__actions">
                <v-btn
                    class="rq-btn"
                    variant="text"
                    :color="quiet ? undefined : 'primary'"
                    size="small"
                    :href="payment.receipt_url"
                    target="_blank"
                    rel="noopener"
                >
                    <v-icon icon="tabler:file-invoice" start />
                    {{ $t('money.receipt') }}
                </v-btn>
                <slot name="actions" :payment="payment" />
            </div>
        </li>
    </ul>
</template>

<script setup>
import { formatDay } from '@/utils/classFormat';

defineProps({
    payments: { type: Array, default: () => [] },
    emptyText: { type: String, default: '' },
    // Optional extra text per row (the class name on «دفعاتي»).
    extra: { type: Function, default: () => '' },
    // History beside the page's main task: amounts in plain text, the receipt link neutral.
    quiet: { type: Boolean, default: false },
});
</script>

<style scoped>
.payment-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.payment-list__row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--rq-line-soft);
}

.payment-list__row:last-child {
    border-bottom: 0;
}

.payment-list__row--void .payment-list__amount {
    color: var(--rq-danger);
    text-decoration: line-through;
}

.payment-list__main {
    flex: 1 1 240px;
    min-width: 0;
}

.payment-list__amount {
    margin-inline-end: 8px;
    font-family: var(--rq-font-display);
    font-size: 16px;
    color: var(--rq-text-number);
}

.payment-list--quiet .payment-list__amount {
    color: var(--rq-text-body);
}

.payment-list--quiet .payment-list__actions .v-btn {
    color: var(--rq-text-muted);
}

.payment-list__meta {
    font-size: 13px;
    color: var(--rq-text-muted);
}

.payment-list__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 4px;
}

.payment-list__empty {
    margin: 0;
    color: var(--rq-text-muted);
}
</style>

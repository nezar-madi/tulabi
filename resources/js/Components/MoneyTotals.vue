<template>
    <div class="money-totals">
        <div class="money-totals__item">
            <span>{{ $t('money.fee') }}</span>
            <strong class="rq-number">{{ $t('money.amount', { amount: totals.fee ?? '0' }) }}</strong>
        </div>
        <div class="money-totals__item">
            <span>{{ $t('money.paid') }}</span>
            <strong class="rq-number">{{ $t('money.amount', { amount: totals.paid }) }}</strong>
        </div>
        <div class="money-totals__item money-totals__item--owed">
            <span>{{ $t('money.remaining') }}</span>
            <strong class="rq-number">{{ $t('money.amount', { amount: totals.remaining }) }}</strong>
        </div>
    </div>
</template>

<script setup>
// Fee, paid and remaining of one enrollment (App\Support\PaymentPresenter::totals()).
defineProps({
    totals: { type: Object, required: true },
});
</script>

<style scoped>
.money-totals {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.money-totals__item {
    display: flex;
    flex-direction: column;
    padding: 12px;
    border-radius: var(--rq-radius-md);
    background: var(--rq-surface-chrome);
    font-size: 13px;
    color: var(--rq-text-muted);
}

.money-totals__item strong {
    font-family: var(--rq-font-display);
    font-size: 18px;
    color: var(--rq-text-body);
}

.money-totals__item--owed {
    background: var(--rq-surface-hover);
}

.money-totals__item--owed strong {
    color: var(--rq-text-number);
}
</style>

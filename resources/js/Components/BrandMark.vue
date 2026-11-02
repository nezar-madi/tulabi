<template>
    <!-- Placeholder text logo until a real one exists. -->
    <span class="brand-mark">
        <span class="brand-mark__tile" aria-hidden="true">ط</span>
        <span class="brand-mark__name">{{ $t('app.name') }}</span>
    </span>
</template>

<style scoped>
.brand-mark {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: var(--rq-font-display);
    color: var(--rq-text-heading);
}

.brand-mark__tile {
    display: inline-grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-action-primary);
    color: var(--rq-text-on-primary);
    font-size: 20px;
    font-weight: 700;
    box-shadow: inset 0 -3px 0 var(--rq-brand-accent);
}

.brand-mark__name {
    font-size: 22px;
    font-weight: 700;
}
</style>

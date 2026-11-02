<template>
    <!-- Text logo until a real one exists: a mark and the wordmark, like rqmana's logo. -->
    <span class="brand-mark" :class="{ 'brand-mark--inverse': inverse }">
        <span class="brand-mark__tile" aria-hidden="true">ط</span>
        <span v-if="!markOnly" class="brand-mark__name">{{ $t('app.name') }}</span>
    </span>
</template>

<script setup>
defineProps({
    // Only the square mark (the collapsed side menu).
    markOnly: { type: Boolean, default: false },
    // White on the deep green (the sign-in page's brand side).
    inverse: { type: Boolean, default: false },
});
</script>

<style scoped>
.brand-mark {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: var(--rq-font-display);
    color: var(--rq-text-heading);
}

.brand-mark__tile {
    display: inline-grid;
    place-items: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--rq-action-primary);
    color: var(--rq-text-on-primary);
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
}

.brand-mark__name {
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}

.brand-mark--inverse {
    color: #FFFFFF;
}

.brand-mark--inverse .brand-mark__tile {
    background: #FFFFFF;
    color: var(--rq-brand-800);
}
</style>

<template>
    <div class="auth-card-wrap">
        <div class="auth-card rq-card" :class="{ 'auth-card--wide': wide }">
            <div class="auth-page__hero">
                <h1>{{ title }}</h1>
                <p v-if="subtitle">{{ subtitle }}</p>
            </div>

            <FlashStatus />

            <slot />
        </div>

        <div v-if="$slots.footer" class="auth-card__footer">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
import FlashStatus from '@/Components/FlashStatus.vue';
import '@/Pages/Auth/authShared.css';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    wide: { type: Boolean, default: false },
});
</script>

<style scoped>
.auth-card-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 16px 64px;
}

.auth-card {
    width: 100%;
    max-width: 460px;
    padding: 32px 28px;
    border-radius: var(--rq-radius-card);
}

.auth-card--wide {
    max-width: 640px;
}

.auth-card__footer {
    margin-top: 20px;
    font-size: 14px;
    color: var(--rq-text-muted);
    text-align: center;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 24px 18px;
    }
}
</style>

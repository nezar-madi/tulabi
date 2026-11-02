<template>
    <Head :title="$t('home.title')" />

    <section class="home-hero">
        <div class="home-hero__inner">
            <p class="rq-eyebrow">{{ $t('home.eyebrow') }}</p>
            <h1 class="home-hero__title rq-display">
                <span class="home-hero__mark">{{ $t('home.headline') }}</span>
            </h1>
            <p class="home-hero__lead">{{ $t('home.lead') }}</p>

            <div class="d-flex flex-wrap align-center ga-3 mt-8">
                <Link :href="route('join')" class="text-decoration-none">
                    <v-btn class="rq-btn rq-btn--primary" size="large">
                        <v-icon icon="tabler:school" start />
                        {{ $t('home.cta_instructor') }}
                    </v-btn>
                </Link>
                <Link v-if="!$page.props.auth?.user" :href="route('register')" class="text-decoration-none">
                    <v-btn class="rq-btn" variant="outlined" color="primary" size="large">
                        <v-icon icon="tabler:user-plus" start />
                        {{ $t('home.cta_student') }}
                    </v-btn>
                </Link>
            </div>

            <p class="home-hero__today mt-6">
                {{ $t('home.today', { date: today }) }} · {{ currency }}
            </p>
        </div>
    </section>

    <section class="home-features">
        <div v-for="feature in features" :key="feature.key" class="rq-card home-feature">
            <span class="rq-icon-tile">
                <v-icon :icon="feature.icon" />
            </span>
            <h2 class="home-feature__title">{{ $t(`home.features.${feature.key}_title`) }}</h2>
            <p class="home-feature__text">{{ $t(`home.features.${feature.key}_text`) }}</p>
        </div>
    </section>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    today: { type: String, required: true },
    currency: { type: String, required: true },
});

const features = [
    { key: 'page', icon: 'tabler:user' },
    { key: 'classes', icon: 'tabler:chalkboard' },
    { key: 'money', icon: 'tabler:receipt' },
];
</script>

<style scoped>
.home-hero {
    background: var(--rq-surface);
    border-bottom: 1px solid var(--rq-line-soft);
}

.home-hero__inner {
    max-width: 1120px;
    margin: 0 auto;
    padding: 72px 16px 64px;
}

.home-hero__title {
    max-width: 720px;
    margin: 16px 0 0;
    font-size: clamp(30px, 5vw, 48px);
    font-weight: 700;
    line-height: 1.35;
    color: var(--rq-text-heading);
}

/* Yellow accent under the headline */
.home-hero__mark {
    background-image: linear-gradient(transparent 72%, var(--rq-brand-accent) 72%, var(--rq-brand-accent) 90%, transparent 90%);
    -webkit-box-decoration-break: clone;
    box-decoration-break: clone;
}

.home-hero__lead {
    max-width: 620px;
    margin: 20px 0 0;
    font-size: 18px;
    line-height: var(--rq-lh-body);
    color: var(--rq-text-muted);
}

.home-hero__today {
    font-size: var(--rq-text-label);
    color: var(--rq-text-subtle);
}

.home-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    max-width: 1120px;
    margin: 0 auto;
    padding: 40px 16px 64px;
}

.home-feature {
    padding: 24px;
    border-radius: var(--rq-radius-card);
}

.home-feature__title {
    margin: 16px 0 6px;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-heading);
}

.home-feature__text {
    margin: 0;
    line-height: var(--rq-lh-body);
    color: var(--rq-text-muted);
}
</style>

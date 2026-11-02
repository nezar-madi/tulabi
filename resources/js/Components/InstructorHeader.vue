<template>
    <section class="instructor-head">
        <div class="instructor-head__inner">
            <div class="instructor-head__avatar">
                <img v-if="instructor.photo_url" :src="instructor.photo_url" :alt="instructor.display_name">
                <span v-else>{{ initial }}</span>
            </div>

            <div class="instructor-head__body">
                <h1 class="instructor-head__name rq-display">
                    <Link v-if="linkName" :href="route('instructors.show', { slug: instructor.slug })">{{ instructor.display_name }}</Link>
                    <template v-else>{{ instructor.display_name }}</template>
                </h1>
                <p class="instructor-head__meta">
                    <span class="rq-eyebrow">{{ instructor.specialty }}</span>
                    <span class="instructor-head__city">
                        <v-icon icon="tabler:map-pin" size="16" />
                        {{ instructor.city }}
                    </span>
                </p>

                <div class="d-flex flex-wrap align-center ga-2 mt-3">
                    <WhatsAppButton :href="instructor.whatsapp">{{ $t('public.contact_whatsapp') }}</WhatsAppButton>
                    <v-btn
                        v-for="link in instructor.links"
                        :key="link.url"
                        icon
                        variant="tonal"
                        color="primary"
                        size="small"
                        :href="link.url"
                        target="_blank"
                        rel="noopener nofollow"
                        :aria-label="link.url"
                    >
                        <v-icon :icon="linkIcon(link.kind)" />
                    </v-btn>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';

const props = defineProps({
    instructor: { type: Object, required: true },
    // On a class page the name links back to the instructor's page.
    linkName: { type: Boolean, default: false },
});

const initial = computed(() => (props.instructor.display_name || '؟').trim().charAt(0));

const ICONS = {
    facebook: 'tabler:brand-facebook',
    instagram: 'tabler:brand-instagram',
    youtube: 'tabler:brand-youtube',
    tiktok: 'tabler:brand-tiktok',
    telegram: 'tabler:brand-telegram',
    x: 'tabler:brand-x',
    linkedin: 'tabler:brand-linkedin',
};

function linkIcon(kind) {
    return ICONS[kind] || 'tabler:world';
}
</script>

<style scoped>
.instructor-head {
    background: var(--rq-surface);
    border-bottom: 1px solid var(--rq-line-soft);
}

.instructor-head__inner {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    align-items: center;
    max-width: 1120px;
    margin: 0 auto;
    padding: 36px 16px;
}

.instructor-head__avatar {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 128px;
    height: 128px;
    overflow: hidden;
    border-radius: 50%;
    border: 3px solid var(--rq-surface);
    box-shadow: 0 0 0 3px var(--rq-brand-accent);
    background: var(--rq-icon-tile);
    color: var(--rq-text-heading);
    font-family: var(--rq-font-display);
    font-size: 48px;
    font-weight: 700;
}

.instructor-head__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.instructor-head__body {
    flex: 1 1 280px;
    min-width: 0;
}

.instructor-head__name {
    margin: 0 0 6px;
    font-size: clamp(26px, 4vw, 36px);
    font-weight: 700;
    color: var(--rq-text-heading);
}

.instructor-head__name a {
    color: inherit;
    text-decoration: none;
}

.instructor-head__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
    margin: 0;
}

.instructor-head__city {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: var(--rq-text-muted);
}
</style>

<template>
    <Head :title="$t('teach.programs.title')" />

    <div class="programs">
        <PageIntro :title="$t('teach.programs.title')" :description="$t('teach.programs.lead')">
            <template #actions>
                <v-btn
                    class="text-none"
                    variant="flat"
                    color="primary"
                    prepend-icon="tabler:plus"
                    @click="router.visit(route('teach.programs.create'))"
                >
                    {{ $t('teach.programs.new') }}
                </v-btn>
            </template>

            <PageIntroStats :items="stats" />
        </PageIntro>

        <v-alert v-if="!hasSlug && programs.length" type="info" variant="tonal" density="comfortable" rounded="lg">
            {{ $t('teach.programs.needs_slug') }}
            <Link :href="route('teach.profile.edit')">{{ $t('teach.nav.profile') }}</Link>
        </v-alert>

        <section v-if="programs.length === 0" class="rq-card programs__empty">
            <span class="rq-icon-tile"><v-icon icon="tabler:layout-grid" /></span>
            <h2>{{ $t('teach.programs.empty_title') }}</h2>
            <p>{{ $t('teach.programs.empty') }}</p>
            <v-btn class="text-none" variant="flat" color="primary" prepend-icon="tabler:plus" @click="router.visit(route('teach.programs.create'))">
                {{ $t('teach.programs.new') }}
            </v-btn>
        </section>

        <div class="programs__grid">
            <article v-for="program in programs" :key="program.id" class="rq-card program" :class="{ 'program--off': !program.is_active }">
                <Link :href="route('teach.programs.edit', program.id)" class="program__media" tabindex="-1" aria-hidden="true">
                    <img v-if="program.image_url" :src="program.image_url" alt="" loading="lazy">
                    <v-icon v-else icon="tabler:photo" size="36" />
                    <span v-if="program.is_featured" class="program__featured">{{ $t('teach.programs.featured') }}</span>
                </Link>
                <div class="program__body">
                    <h2 class="program__name">
                        <Link :href="route('teach.programs.edit', program.id)">{{ program.name }}</Link>
                    </h2>
                    <div class="program__badges">
                        <span class="rq-badge" :class="program.is_active ? 'rq-badge--success' : ''">
                            {{ program.is_active ? $t('teach.programs.active') : $t('teach.programs.inactive') }}
                        </span>
                        <span class="rq-badge" :class="program.is_published ? 'rq-badge--info' : ''">
                            {{ program.is_published ? $t('teach.programs.published') : $t('teach.programs.hidden') }}
                        </span>
                        <span class="rq-badge">{{ $t('teach.programs.classes_count', { count: program.classes_count }) }}</span>
                    </div>
                    <div class="program__actions">
                        <v-btn
                            v-if="program.public_url && program.is_active && program.is_published"
                            class="rq-btn"
                            variant="text"
                            color="primary"
                            :href="program.public_url"
                            target="_blank"
                            rel="noopener"
                        >
                            {{ $t('teach.programs.view') }}
                        </v-btn>
                        <Link :href="route('teach.programs.edit', program.id)" class="text-decoration-none">
                            <v-btn class="rq-btn" variant="tonal" color="primary">{{ $t('teach.programs.edit') }}</v-btn>
                        </Link>
                        <Link
                            v-if="program.is_active"
                            :href="route('teach.classes.create', { program: program.id })"
                            class="text-decoration-none"
                        >
                            <v-btn class="rq-btn" variant="text" color="primary">{{ $t('teach.programs.new_class_short') }}</v-btn>
                        </Link>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import PageIntro from '@/Components/PageIntro.vue';
import PageIntroStats from '@/Components/PageIntroStats.vue';

const props = defineProps({
    programs: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ total: 0, active: 0, published: 0 }) },
    hasSlug: { type: Boolean, default: false },
});

const { t } = useI18n();

const stats = computed(() => [
    { key: 'total', label: t('teach.programs.stat_total'), value: props.counts.total, icon: 'tabler:layout-grid', tone: 'sky' },
    { key: 'active', label: t('teach.programs.stat_active'), value: props.counts.active, icon: 'tabler:circle-check', tone: 'success' },
    { key: 'published', label: t('teach.programs.stat_published'), value: props.counts.published, icon: 'tabler:world', tone: 'teal' },
]);
</script>

<style scoped>
.programs {
    display: flex;
    flex-direction: column;
    gap: var(--rq-space-5);
}

.programs__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 40px 24px;
    border-radius: var(--rq-radius-card);
    text-align: center;
}

.programs__empty h2 {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    color: var(--rq-text-heading);
}

.programs__empty p {
    max-width: 440px;
    margin: 0 0 6px;
    color: var(--rq-text-muted);
}

.programs__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.program {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: var(--rq-radius-card);
}

.program--off {
    opacity: 0.75;
}

.program__media {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 16 / 10;
    background: var(--rq-surface-tint);
    color: var(--rq-text-subtle);
}

.program__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.program__featured {
    position: absolute;
    top: 10px;
    inset-inline-start: 10px;
    padding: 3px 10px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-action-primary);
    font-family: var(--rq-font-display);
    font-size: 12px;
    color: var(--rq-text-on-primary);
}

.program__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 10px;
    padding: 16px 18px 14px;
}

.program__name {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: 17px;
    font-weight: 600;
}

.program__name a {
    color: var(--rq-text-heading);
    text-decoration: none;
}

.program__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.program__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--rq-line-soft);
}
</style>

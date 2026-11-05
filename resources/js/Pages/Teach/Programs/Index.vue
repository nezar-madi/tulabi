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
                    <span v-else-if="program.promo_video_url" class="program__noimage">
                        <v-icon icon="tabler:player-play" size="30" />
                        <small>{{ $t('teach.programs.card.video_only') }}</small>
                    </span>
                    <span v-else class="program__noimage">
                        <v-icon icon="tabler:photo" size="30" />
                        <small>{{ $t('teach.programs.card.no_image') }}</small>
                    </span>
                    <span v-if="program.is_featured" class="program__chip program__chip--featured">
                        <v-icon icon="tabler:star" size="13" />
                        {{ $t('teach.programs.featured') }}
                    </span>
                    <span class="program__chip program__chip--state" :class="{ 'program__chip--live': isShown(program) }">
                        <v-icon :icon="isShown(program) ? 'tabler:eye' : 'tabler:eye-off'" size="13" />
                        {{ isShown(program) ? $t('teach.programs.published') : $t('teach.programs.hidden') }}
                    </span>
                </Link>

                <div class="program__body">
                    <h2 class="program__name">
                        <Link :href="route('teach.programs.edit', program.id)">{{ program.name }}</Link>
                    </h2>
                    <p v-if="program.description" class="program__desc">{{ program.description }}</p>
                    <div class="program__meta">
                        <span class="program__status" :class="{ 'program__status--on': program.is_active }">
                            {{ program.is_active ? $t('teach.programs.active') : $t('teach.programs.inactive') }}
                        </span>
                        <span class="program__count">
                            <v-icon icon="tabler:school" size="15" />
                            {{ $t('teach.programs.classes_count', { count: program.classes_count }) }}
                        </span>
                    </div>
                </div>

                <footer class="program__foot">
                    <Link
                        v-if="program.is_active"
                        :href="route('teach.classes.create', { program: program.id })"
                        class="program__new"
                    >
                        <v-icon icon="tabler:plus" size="16" />
                        {{ $t('teach.programs.card.new_class') }}
                    </Link>
                    <span v-else class="program__paused">{{ $t('teach.programs.card.paused') }}</span>

                    <div class="program__tools">
                        <v-btn
                            v-if="isShown(program)"
                            icon
                            variant="text"
                            size="small"
                            class="program__tool"
                            :href="program.public_url"
                            target="_blank"
                            rel="noopener"
                            :aria-label="$t('teach.programs.view')"
                        >
                            <v-icon icon="tabler:external-link" size="18" />
                            <v-tooltip activator="parent" location="top">{{ $t('teach.programs.view') }}</v-tooltip>
                        </v-btn>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            class="program__tool"
                            :aria-label="$t('teach.programs.edit')"
                            @click="router.visit(route('teach.programs.edit', program.id))"
                        >
                            <v-icon icon="tabler:pencil" size="18" />
                            <v-tooltip activator="parent" location="top">{{ $t('teach.programs.edit') }}</v-tooltip>
                        </v-btn>
                    </div>
                </footer>
            </article>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PageIntro from '@/Components/PageIntro.vue';

const props = defineProps({
    programs: { type: Array, default: () => [] },
    hasSlug: { type: Boolean, default: false },
});

// Visitors see a program only when it is active, shown, has an image or a video, and the instructor has a page address.
function isShown(program) {
    return Boolean(program.public_url && (program.image_url || program.promo_video_url) && program.is_active && program.is_published);
}
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
    opacity: 0.8;
}

.program__media {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--rq-surface-tint);
    color: var(--rq-text-subtle);
    text-decoration: none;
}

.program__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--rq-dur) var(--rq-ease);
}

.program:hover .program__media img {
    transform: scale(1.03);
}

.program__noimage {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 12px;
}

.program__chip {
    position: absolute;
    top: 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: var(--rq-radius-pill);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
    color: #fff;
    backdrop-filter: blur(6px);
}

.program__chip--featured {
    inset-inline-start: 10px;
    background: var(--rq-accent-amber);
}

.program__chip--state {
    inset-inline-end: 10px;
    background: rgb(15 23 42 / 0.6);
}

.program__chip--live {
    background: rgb(21 128 61 / 0.88);
}

.program__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 8px;
    padding: 16px 18px 14px;
}

.program__name {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: 17px;
    font-weight: 600;
    line-height: 1.5;
}

.program__name a {
    color: var(--rq-text-heading);
    text-decoration: none;
}

.program__name a:hover {
    color: var(--rq-text-link);
}

.program__desc {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    font-size: 13px;
    line-height: 1.7;
    color: var(--rq-text-muted);
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.program__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14px;
    margin-top: auto;
    padding-top: 4px;
    font-size: 13px;
    color: var(--rq-text-muted);
}

.program__status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.program__status::before {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--rq-text-subtle);
    content: "";
}

.program__status--on {
    color: var(--rq-success);
}

.program__status--on::before {
    background: var(--rq-success);
    box-shadow: 0 0 0 3px var(--rq-success-tint);
}

.program__count {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.program__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-block: 8px;
    padding-inline: 18px 8px;
    border-top: 1px solid var(--rq-line-soft);
    background: var(--rq-surface-hover);
}

.program__new {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding-block: 5px;
    padding-inline: 10px 14px;
    border-radius: var(--rq-radius-pill);
    background: var(--rq-icon-tile);
    font-family: var(--rq-font-display);
    font-size: 13px;
    font-weight: 600;
    color: var(--rq-action-primary);
    text-decoration: none;
    transition: background-color var(--rq-dur-fast) var(--rq-ease), color var(--rq-dur-fast) var(--rq-ease);
}

.program__new:hover {
    background: var(--rq-action-primary);
    color: var(--rq-text-on-primary);
}

.program__new:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}

.program__paused {
    font-size: 12px;
    color: var(--rq-text-subtle);
}

.program__tools {
    display: flex;
    gap: 2px;
}

.program__tool {
    color: var(--rq-text-muted);
}

.program__tool:hover {
    color: var(--rq-text-link);
}
</style>

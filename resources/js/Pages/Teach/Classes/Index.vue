<template>
    <Head :title="$t('teach.classes.title')" />

    <div class="d-flex flex-wrap align-center justify-space-between ga-3 mb-5">
        <h1 class="teach-title rq-display">{{ $t('teach.classes.title') }}</h1>
        <Link :href="route('teach.classes.create')" class="text-decoration-none">
            <v-btn class="rq-btn rq-btn--primary">
                <v-icon icon="tabler:plus" start />
                {{ $t('teach.classes.new') }}
            </v-btn>
        </Link>
    </div>

    <v-alert v-if="!hasSlug" type="info" variant="tonal" density="comfortable" class="mb-4" rounded="lg">
        {{ $t('teach.classes.needs_slug') }}
        <Link :href="route('teach.profile.edit')">{{ $t('teach.nav.profile') }}</Link>
    </v-alert>

    <div class="teach-tabs">
        <Link
            v-for="tab in tabs"
            :key="tab"
            :href="route('teach.classes.index', { filter: tab })"
            class="teach-tabs__tab"
            :class="{ 'teach-tabs__tab--active': filter === tab }"
        >
            {{ $t(`teach.classes.filters.${tab}`) }}
        </Link>
    </div>

    <p v-if="classes.data.length === 0" class="rq-card teach-empty">{{ $t('teach.classes.empty') }}</p>

    <div class="class-grid">
        <ClassCard v-for="cls in classes.data" :key="cls.id" :cls="cls" show-status>
            <template #actions>
                <div class="d-flex ga-1">
                    <v-btn
                        v-if="slug && cls.status !== 'canceled'"
                        class="rq-btn"
                        variant="text"
                        color="primary"
                        :href="route('classes.show', { slug, class: cls.id })"
                        target="_blank"
                        rel="noopener"
                    >
                        {{ $t('teach.classes.view') }}
                    </v-btn>
                    <Link :href="route('teach.classes.edit', cls.id)" class="text-decoration-none">
                        <v-btn class="rq-btn" variant="tonal" color="primary">{{ $t('teach.classes.edit') }}</v-btn>
                    </Link>
                </div>
            </template>
        </ClassCard>
    </div>

    <Pager :paginator="classes" />
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClassCard from '@/Components/ClassCard.vue';
import Pager from '@/Components/Pager.vue';

defineProps({
    filter: { type: String, required: true },
    classes: { type: Object, required: true },
    hasSlug: { type: Boolean, default: false },
    slug: { type: String, default: null },
});

const tabs = ['active', 'draft', 'canceled'];
</script>

<style scoped src="../teach.css"></style>

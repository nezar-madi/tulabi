<template>
    <Head :title="$t('teach.classes.title')" />

    <PageIntro :title="$t('teach.classes.title')">
        <template #actions>
            <CtaButton icon="tabler:plus" @click="router.visit(route('teach.classes.create'))">
                {{ $t('teach.classes.new') }}
            </CtaButton>
        </template>
    </PageIntro>

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
        <TeachClassCard v-for="cls in classes.data" :key="cls.id" :cls="cls" :slug="slug" :today="today" />
    </div>

    <Pager :paginator="classes" />
</template>

<script setup>
import CtaButton from '@/Components/CtaButton.vue';
import PageIntro from '@/Components/PageIntro.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TeachClassCard from '@/Components/TeachClassCard.vue';
import Pager from '@/Components/Pager.vue';

defineProps({
    filter: { type: String, required: true },
    classes: { type: Object, required: true },
    hasSlug: { type: Boolean, default: false },
    slug: { type: String, default: null },
    today: { type: String, required: true },
});

const tabs = ['active', 'draft', 'canceled'];
</script>

<style scoped src="../teach.css"></style>

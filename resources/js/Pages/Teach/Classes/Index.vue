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
                    <Link v-if="cls.status !== 'draft'" :href="route('teach.classes.students', cls.id)" class="text-decoration-none">
                        <v-btn class="rq-btn" variant="text" color="primary">{{ $t('teach.students.open') }}</v-btn>
                    </Link>
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
import CtaButton from '@/Components/CtaButton.vue';
import PageIntro from '@/Components/PageIntro.vue';
import { Head, Link, router } from '@inertiajs/vue3';
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

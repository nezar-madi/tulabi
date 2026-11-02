<template>
    <Head :title="instructor.display_name">
        <meta name="description" :content="`${instructor.display_name} - ${instructor.specialty}، ${instructor.city}`">
    </Head>

    <div v-if="isOwner" class="owner-bar">
        <span>{{ $t('public.owner_banner') }}</span>
        <Link :href="route('teach.profile.edit')">{{ $t('public.edit_page') }}</Link>
    </div>

    <InstructorHeader :instructor="instructor" />

    <div class="public-page">
        <section v-if="instructor.bio" class="rq-card public-bio">{{ instructor.bio }}</section>

        <h2 class="public-subtitle">{{ $t('public.classes_title') }}</h2>
        <p v-if="classes.length === 0" class="rq-card public-empty">{{ $t('public.no_classes') }}</p>

        <div class="public-grid">
            <ClassCard v-for="cls in classes" :key="cls.id" :cls="cls" :show-status="cls.status === 'closed'">
                <template #actions>
                    <Link :href="route('classes.show', { slug: instructor.slug, class: cls.id })" class="text-decoration-none">
                        <v-btn class="rq-btn rq-btn--primary">{{ $t('public.view_class') }}</v-btn>
                    </Link>
                </template>
            </ClassCard>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClassCard from '@/Components/ClassCard.vue';
import InstructorHeader from '@/Components/InstructorHeader.vue';

defineProps({
    instructor: { type: Object, required: true },
    classes: { type: Array, default: () => [] },
    isOwner: { type: Boolean, default: false },
});
</script>

<style scoped src="./public.css"></style>

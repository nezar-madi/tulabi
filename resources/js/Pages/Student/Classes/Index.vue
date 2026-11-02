<template>
    <Head :title="$t('student.classes.title')" />

    <div class="student-page">
        <PageIntro :title="$t('student.classes.title')" />

        <p v-if="enrollments.length === 0" class="rq-card student-page__empty">{{ $t('student.classes.empty') }}</p>

        <div class="student-page__grid">
            <ClassCard v-for="row in enrollments" :key="row.id" :cls="row.class">
                <template #actions>
                    <div class="d-flex align-center ga-2">
                        <EnrollmentStatusBadge :status="row.status" />
                        <Link :href="route('student.classes.show', row.id)" class="text-decoration-none">
                            <v-btn class="rq-btn" variant="tonal" color="primary">{{ $t('student.classes.open') }}</v-btn>
                        </Link>
                    </div>
                </template>
            </ClassCard>
        </div>
    </div>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { Head, Link } from '@inertiajs/vue3';
import ClassCard from '@/Components/ClassCard.vue';
import EnrollmentStatusBadge from '@/Components/EnrollmentStatusBadge.vue';

defineProps({
    enrollments: { type: Array, default: () => [] },
});
</script>

<style scoped src="./student.css"></style>

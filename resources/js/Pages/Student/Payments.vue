<template>
    <Head :title="$t('student.payments.title')" />

    <div class="student-page">
        <PageIntro :title="$t('student.payments.title')" />

        <section class="rq-card student-payments">
            <PaymentList
                :payments="payments"
                :empty-text="$t('student.payments.empty')"
                :extra="(payment) => `${payment.class_title} (${payment.instructor})`"
            />
        </section>
    </div>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { Head } from '@inertiajs/vue3';
import PaymentList from '@/Components/PaymentList.vue';

defineProps({
    payments: { type: Array, default: () => [] },
});
</script>

<style scoped src="./Classes/student.css"></style>

<style scoped>
.student-payments {
    padding: 8px 24px;
    border-radius: var(--rq-radius-card);
}
</style>

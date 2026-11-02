<template>
    <span class="rq-badge" :class="badgeClass">
        {{ $t(forInstructor ? `enrollment.status_instructor.${status}` : `enrollment.status.${status}`) }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    // The instructor reads "بانتظار ردّك" / "انسحب" instead of the student's wording.
    forInstructor: { type: Boolean, default: false },
});

const badgeClass = computed(() => ({
    requested: 'rq-badge--warning',
    accepted: 'rq-badge--success',
    rejected: 'rq-badge--danger',
    canceled: 'rq-badge--danger',
    withdrawn: '',
}[props.status] ?? ''));
</script>

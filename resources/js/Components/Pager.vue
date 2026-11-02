<template>
    <div v-if="paginator.last_page > 1" class="d-flex align-center justify-center ga-3 mt-5">
        <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url" preserve-scroll class="text-decoration-none">
            <v-btn class="rq-btn" variant="outlined" color="primary">{{ $t('common.previous') }}</v-btn>
        </Link>
        <span class="text-medium-emphasis">{{ paginator.current_page }} / {{ paginator.last_page }}</span>
        <Link v-if="paginator.next_page_url" :href="paginator.next_page_url" preserve-scroll class="text-decoration-none">
            <v-btn class="rq-btn" variant="outlined" color="primary">{{ $t('common.next') }}</v-btn>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

// Laravel's length-aware paginator as sent by Inertia.
defineProps({
    paginator: { type: Object, required: true },
});
</script>

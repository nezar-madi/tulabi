<template>
    <Head :title="$t('teach.requests.title')" />

    <h1 class="teach-title rq-display mb-5">{{ $t('teach.requests.title') }}</h1>

    <p v-if="requests.length === 0" class="rq-card teach-empty">{{ $t('teach.requests.empty') }}</p>

    <article v-for="item in requests" :key="item.id" class="rq-card request">
        <div class="request__main">
            <p class="request__class">
                <Link :href="route('teach.classes.students', item.class.id)">{{ item.class.title }}</Link>
            </p>
            <h2 class="request__name">{{ item.student?.name }}</h2>
            <p class="request__meta">
                <span dir="ltr">{{ item.student?.phone }}</span>
                · {{ $t('teach.requests.requested_on', { date: formatDate(item.requested_at) }) }}
            </p>
            <p v-if="item.note" class="request__note">
                <v-icon icon="tabler:note" size="16" />
                {{ item.note }}
            </p>
            <p v-if="item.class.seats && item.accepted_count >= item.class.seats" class="request__warn">
                {{ $t('teach.requests.seats_full', { accepted: item.accepted_count, seats: item.class.seats }) }}
            </p>
        </div>

        <div class="request__actions">
            <WhatsAppButton :href="item.student?.whatsapp" />
            <v-btn class="rq-btn rq-btn--primary" :loading="busy === item.id" @click="accept(item)">
                <v-icon icon="tabler:check" start />
                {{ $t('teach.requests.accept') }}
            </v-btn>
            <v-btn class="rq-btn" variant="outlined" color="error" @click="openReject(item)">
                {{ $t('teach.requests.reject') }}
            </v-btn>
        </div>
    </article>

    <ReasonDialog
        v-model="rejectOpen"
        :url="rejecting ? route('teach.enrollments.reject', rejecting.id) : ''"
        :title="$t('teach.requests.reject_title', { name: rejecting?.student?.name || '' })"
        :confirm-label="$t('teach.requests.reject')"
    />
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ReasonDialog from '@/Components/ReasonDialog.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';
import { formatDate } from '@/utils/dates';

defineProps({
    requests: { type: Array, default: () => [] },
});

const busy = ref(null);
const rejectOpen = ref(false);
const rejecting = ref(null);

function accept(item) {
    busy.value = item.id;
    router.post(route('teach.enrollments.accept', item.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = null;
        },
    });
}

function openReject(item) {
    rejecting.value = item;
    rejectOpen.value = true;
}
</script>

<style scoped src="./teach.css"></style>

<style scoped>
.request {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: space-between;
    padding: 20px;
    margin-bottom: 10px;
    border-radius: var(--rq-radius-card);
}

.request__main {
    flex: 1 1 300px;
    min-width: 0;
}

.request__class {
    margin: 0 0 4px;
    font-size: 13px;
}

.request__class a {
    color: var(--rq-text-eyebrow);
    text-decoration: none;
}

.request__name {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-heading);
}

.request__meta {
    margin: 2px 0 0;
    font-size: 14px;
    color: var(--rq-text-muted);
}

.request__note {
    display: flex;
    gap: 6px;
    margin: 10px 0 0;
    padding: 8px 10px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-size: 14px;
    white-space: pre-line;
}

.request__warn {
    margin: 8px 0 0;
    font-size: 13px;
    color: var(--rq-warning);
}

.request__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 8px;
}
</style>

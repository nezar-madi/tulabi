<template>
    <Head :title="$t('admin.nav.applications')" />

    <h1 class="admin-title rq-display">{{ $t('admin.nav.applications') }}</h1>

    <v-alert v-if="welcome" type="success" variant="tonal" class="mb-5" rounded="lg">
        <div class="d-flex flex-wrap align-center justify-space-between ga-3">
            <div>
                <strong>{{ $t('admin.applications.welcome_title', { name: welcome.name }) }}</strong>
                <div>{{ $t('admin.applications.welcome_text') }}</div>
            </div>
            <v-btn
                v-if="welcome.whatsapp"
                class="rq-btn rq-btn--primary"
                :href="welcome.whatsapp"
                target="_blank"
                rel="noopener"
            >
                <v-icon icon="tabler:brand-whatsapp" start />
                {{ $t('admin.applications.welcome_button') }}
            </v-btn>
        </div>
    </v-alert>

    <div class="admin-tabs mb-4">
        <Link
            v-for="tab in tabs"
            :key="tab"
            :href="route('admin.applications.index', { status: tab })"
            class="admin-tabs__tab"
            :class="{ 'admin-tabs__tab--active': status === tab }"
        >
            {{ $t(`admin.applications.status.${tab}`) }}
            <span class="admin-tabs__count rq-number">{{ counts[tab] || 0 }}</span>
        </Link>
    </div>

    <p v-if="applications.data.length === 0" class="rq-card admin-empty">{{ $t('common.empty') }}</p>

    <article v-for="item in applications.data" :key="item.id" class="rq-card admin-item">
        <div class="admin-item__main">
            <h2 class="admin-item__name">{{ item.name }}</h2>
            <p class="admin-item__meta">
                <span dir="ltr">{{ item.phone }}</span>
                <span v-if="item.email" dir="ltr"> · {{ item.email }}</span>
            </p>
            <p class="admin-item__meta">
                <strong>{{ item.specialty }}</strong> · {{ item.city }}
            </p>
            <p v-if="item.bio" class="admin-item__bio">{{ item.bio }}</p>
            <p class="admin-item__date">
                {{ $t('admin.applications.submitted_at', { date: formatDate(item.created_at) }) }}
                <template v-if="item.reviewed_at">
                    · {{ $t('admin.applications.reviewed', { date: formatDate(item.reviewed_at), name: item.reviewer || '-' }) }}
                </template>
            </p>
            <p v-if="item.rejection_reason" class="admin-item__reason">{{ item.rejection_reason }}</p>
        </div>

        <div class="admin-item__actions">
            <v-btn
                v-if="item.whatsapp"
                class="rq-btn"
                variant="text"
                color="primary"
                :href="item.whatsapp"
                target="_blank"
                rel="noopener"
            >
                <v-icon icon="tabler:brand-whatsapp" start />
                {{ $t('common.whatsapp') }}
            </v-btn>
            <template v-if="item.status === 'pending'">
                <v-btn class="rq-btn rq-btn--primary" :loading="busy === item.id" @click="accept(item)">
                    <v-icon icon="tabler:check" start />
                    {{ $t('admin.applications.accept') }}
                </v-btn>
                <v-btn class="rq-btn" variant="outlined" color="error" @click="openReject(item)">
                    {{ $t('admin.applications.reject') }}
                </v-btn>
            </template>
        </div>
    </article>

    <Pager :paginator="applications" />

    <v-dialog v-model="rejectDialog" max-width="480">
        <v-card class="app-form-dialog">
            <v-card-text class="pa-6">
                <h2 class="rq-dialog__title mb-4">{{ $t('admin.applications.reject_title', { name: rejecting?.name || '' }) }}</h2>
                <FormPlainTextarea
                    v-model="rejectForm.reason"
                    :label="$t('admin.applications.reason')"
                    :placeholder="$t('admin.applications.reason_hint')"
                    :rows="3"
                    :maxlength="500"
                    :error-messages="rejectForm.errors.reason"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="rejectDialog = false">{{ $t('common.cancel') }}</v-btn>
                <v-btn class="rq-btn" variant="flat" color="error" :loading="rejectForm.processing" @click="reject">
                    {{ $t('admin.applications.reject') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import Pager from '@/Components/Pager.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import { formatDate } from '@/utils/dates';

defineProps({
    status: { type: String, required: true },
    applications: { type: Object, required: true },
    counts: { type: Object, default: () => ({}) },
    welcome: { type: Object, default: null },
});

const tabs = ['pending', 'accepted', 'rejected'];
const busy = ref(null);
const rejectDialog = ref(false);
const rejecting = ref(null);
const rejectForm = useForm({ reason: '' });

function accept(item) {
    busy.value = item.id;
    router.post(route('admin.applications.accept', item.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = null;
        },
    });
}

function openReject(item) {
    rejecting.value = item;
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectDialog.value = true;
}

function reject() {
    rejectForm.post(route('admin.applications.reject', rejecting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialog.value = false;
        },
    });
}
</script>

<style scoped src="../adminList.css"></style>

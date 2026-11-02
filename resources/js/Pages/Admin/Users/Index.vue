<template>
    <Head :title="title" />

    <PageIntro :title="title" />

    <form class="d-flex ga-2 mb-4" @submit.prevent="search">
        <FormPlainTextField
            v-model="query"
            :placeholder="$t('admin.users.search')"
            :field-attrs="{ name: 'search', type: 'search' }"
            class="flex-grow-1"
        />
        <v-btn type="submit" class="rq-btn rq-btn--primary align-self-start mt-1" size="large">
            <v-icon icon="tabler:search" start />
            {{ $t('common.search') }}
        </v-btn>
    </form>

    <p v-if="users.data.length === 0" class="rq-card admin-empty">{{ $t('common.empty') }}</p>

    <article v-for="user in users.data" :key="user.id" class="rq-card admin-item">
        <div class="admin-item__main">
            <h2 class="admin-item__name">
                {{ user.name }}
                <span v-if="user.status === 'suspended'" class="rq-badge rq-badge--danger ms-2">{{ $t('admin.users.suspended') }}</span>
            </h2>
            <p class="admin-item__meta">
                <span dir="ltr">{{ user.phone }}</span>
                <span v-if="user.email" dir="ltr"> · {{ user.email }}</span>
            </p>
            <p v-if="user.specialty" class="admin-item__meta">
                <strong>{{ user.specialty }}</strong> · {{ user.city }}
            </p>
            <p class="admin-item__date">{{ $t('admin.users.joined', { date: formatDate(user.created_at) }) }}</p>
        </div>

        <div class="admin-item__actions">
            <v-btn
                v-if="user.whatsapp"
                class="rq-btn"
                variant="text"
                color="primary"
                :href="user.whatsapp"
                target="_blank"
                rel="noopener"
            >
                <v-icon icon="tabler:brand-whatsapp" start />
                {{ $t('common.whatsapp') }}
            </v-btn>
            <v-btn
                v-if="user.status === 'active'"
                class="rq-btn"
                variant="outlined"
                color="primary"
                @click="post('admin.users.reset-link', user)"
            >
                <v-icon icon="tabler:key" start />
                {{ $t('admin.users.reset_link') }}
            </v-btn>
            <v-btn
                v-if="user.status === 'active'"
                class="rq-btn"
                variant="outlined"
                color="error"
                @click="suspend(user)"
            >
                {{ $t('admin.users.suspend') }}
            </v-btn>
            <v-btn
                v-else
                class="rq-btn rq-btn--primary"
                @click="post('admin.users.activate', user)"
            >
                {{ $t('admin.users.activate') }}
            </v-btn>
        </div>
    </article>

    <Pager :paginator="users" />

    <v-dialog :model-value="Boolean(resetLink) && showLink" max-width="520" @update:model-value="showLink = $event">
        <v-card v-if="resetLink" class="app-form-dialog">
            <v-card-text class="pa-6">
                <h2 class="rq-dialog__title mb-2">{{ $t('admin.users.reset_title', { name: resetLink.name }) }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ $t('admin.users.reset_text') }}</p>
                <div class="reset-link mb-4" dir="ltr">{{ resetLink.url }}</div>
                <div class="d-flex flex-wrap ga-2">
                    <v-btn
                        v-if="resetLink.whatsapp"
                        class="rq-btn rq-btn--primary"
                        :href="resetLink.whatsapp"
                        target="_blank"
                        rel="noopener"
                    >
                        <v-icon icon="tabler:brand-whatsapp" start />
                        {{ $t('admin.users.send_whatsapp') }}
                    </v-btn>
                    <CopyButton :text="resetLink.url" />
                </div>
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="showLink = false">{{ $t('common.close') }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import CopyButton from '@/Components/CopyButton.vue';
import Pager from '@/Components/Pager.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';
import { formatDate } from '@/utils/dates';

const props = defineProps({
    kind: { type: String, required: true },
    users: { type: Object, required: true },
    search: { type: String, default: '' },
    resetLink: { type: Object, default: null },
});

const { t } = useI18n();
const title = computed(() => t(props.kind === 'instructors' ? 'admin.users.instructors_title' : 'admin.users.students_title'));
const listRoute = computed(() => (props.kind === 'instructors' ? 'admin.instructors.index' : 'admin.students.index'));

const query = ref(props.search);
const showLink = ref(true);

// A new link arrives with the page after "رابط تعيين كلمة المرور": show it.
watch(() => props.resetLink, (link) => {
    if (link) {
        showLink.value = true;
    }
});

function search() {
    router.get(route(listRoute.value), query.value ? { search: query.value } : {}, { preserveState: true });
}

function post(name, user) {
    router.post(route(name, user.id), {}, { preserveScroll: true });
}

function suspend(user) {
    if (window.confirm(t('admin.users.suspend_confirm', { name: user.name }))) {
        post('admin.users.suspend', user);
    }
}
</script>

<style scoped src="../adminList.css"></style>

<style scoped>
.reset-link {
    padding: 10px 12px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 13px;
    word-break: break-all;
    user-select: all;
}
</style>

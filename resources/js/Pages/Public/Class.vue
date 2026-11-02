<template>
    <Head :title="`${cls.title} - ${instructor.display_name}`" />

    <div v-if="isOwner" class="owner-bar">
        <span>{{ cls.status === 'draft' ? $t('public.draft_banner') : $t('public.owner_banner') }}</span>
        <Link :href="route('teach.classes.edit', cls.id)">{{ $t('public.edit_class') }}</Link>
    </div>

    <InstructorHeader :instructor="instructor" link-name />

    <div class="public-page">
        <Link :href="route('instructors.show', { slug: instructor.slug })" class="class-page__back">
            <v-icon icon="tabler:arrow-right" size="18" />
            {{ $t('public.back', { name: instructor.display_name }) }}
        </Link>

        <FlashStatus />
        <v-alert v-if="$page.props.errors?.enrollment" type="error" variant="tonal" class="mb-5" rounded="lg">
            {{ $page.props.errors.enrollment }}
        </v-alert>

        <div class="class-page">
            <article class="rq-card class-page__main">
                <div class="d-flex flex-wrap ga-2 mb-3">
                    <span class="rq-badge" :class="cls.type === 'online' ? 'rq-badge--info' : ''">
                        {{ $t(`class.type.${cls.type}`) }}
                    </span>
                    <ClassStatusBadge v-if="cls.status !== 'published' || cls.ended" :status="cls.status" :ended="cls.ended" />
                </div>
                <h1 class="class-page__title rq-display">{{ cls.title }}</h1>
                <!-- Filtered on save by App\Support\SafeHtml::description(). -->
                <div v-if="cls.description" class="class-page__description" v-html="cls.description" />
            </article>

            <aside class="rq-card class-page__side">
                <dl class="class-page__facts">
                    <div>
                        <dt>{{ $t('public.schedule') }}</dt>
                        <dd>
                            {{ formatPeriod(cls, t) }}
                            <template v-if="schedule"><br>{{ schedule }}</template>
                        </dd>
                    </div>
                    <div>
                        <dt>{{ $t('public.place') }}</dt>
                        <dd v-if="cls.type === 'in_person'">{{ cls.location }}</dd>
                        <dd v-else>{{ $t('class.type.online') }} · {{ $t('public.online_note') }}</dd>
                    </div>
                    <div v-if="cls.seats">
                        <dt>{{ $t('public.seats') }}</dt>
                        <dd class="rq-number">{{ cls.seats }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('public.price') }}</dt>
                        <dd class="class-page__price rq-number">{{ formatPrice(cls, t) }}</dd>
                    </div>
                </dl>

                <p v-if="cls.ended" class="class-page__note">{{ $t('public.ended') }}</p>
                <p v-else-if="isOwner" class="class-page__note">{{ $t('public.owner_no_request') }}</p>

                <!-- The visitor already has a place (or a request) in this class. -->
                <div v-else-if="enrollment && ['requested', 'accepted'].includes(enrollment.status)" class="class-page__mine">
                    <EnrollmentStatusBadge :status="enrollment.status" />
                    <Link :href="route('student.classes.show', enrollment.id)" class="text-decoration-none">
                        <v-btn class="rq-btn" variant="outlined" color="primary" block>{{ $t('public.see_my_classes') }}</v-btn>
                    </Link>
                </div>

                <p v-else-if="cls.status === 'closed'" class="class-page__note">{{ $t('public.closed') }}</p>

                <template v-else-if="acceptsRequests">
                    <p v-if="enrollment" class="class-page__note mb-2">
                        {{ $t('public.your_status', { status: $t(`enrollment.status.${enrollment.status}`) }) }}
                    </p>
                    <v-btn v-if="signedIn" class="rq-btn rq-btn--primary" block size="large" @click="requestOpen = true">
                        {{ enrollment ? $t('public.request_again') : $t('public.request') }}
                    </v-btn>
                    <template v-else>
                        <!-- Signing in comes back here with the request box open (JoinClassController::create). -->
                        <Link :href="route('classes.request.create', cls.id)" class="text-decoration-none">
                            <v-btn class="rq-btn rq-btn--primary" block size="large">{{ $t('public.request') }}</v-btn>
                        </Link>
                        <p class="class-page__note mt-2">{{ $t('public.login_to_request') }}</p>
                    </template>
                </template>
            </aside>
        </div>
    </div>

    <v-dialog v-model="requestOpen" max-width="480">
        <v-card class="app-form-dialog">
            <v-card-text class="pa-6">
                <h2 class="rq-dialog__title mb-2">{{ $t('public.request_title', { title: cls.title }) }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ $t('public.request_text') }}</p>
                <FormPlainTextarea
                    v-model="requestForm.note"
                    :label="$t('public.request_note')"
                    :placeholder="$t('public.request_note_hint')"
                    :rows="3"
                    :maxlength="500"
                    :error-messages="requestForm.errors.note"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="requestOpen = false">{{ $t('common.cancel') }}</v-btn>
                <v-btn class="rq-btn rq-btn--primary" :loading="requestForm.processing" @click="sendRequest">
                    {{ $t('public.request_send') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ClassStatusBadge from '@/Components/ClassStatusBadge.vue';
import EnrollmentStatusBadge from '@/Components/EnrollmentStatusBadge.vue';
import FlashStatus from '@/Components/FlashStatus.vue';
import InstructorHeader from '@/Components/InstructorHeader.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import { formatPeriod, formatPrice, formatSchedule } from '@/utils/classFormat';

const props = defineProps({
    instructor: { type: Object, required: true },
    item: { type: Object, required: true },
    isOwner: { type: Boolean, default: false },
    acceptsRequests: { type: Boolean, default: false },
    enrollment: { type: Object, default: null },
});

const { t } = useI18n();
const page = usePage();
const cls = computed(() => props.item);
const schedule = computed(() => formatSchedule(cls.value, t));
const signedIn = computed(() => Boolean(page.props.auth?.user));

const requestOpen = ref(false);
const requestForm = useForm({ note: '' });

// Back from signing in with ?request=1: open the box straight away.
onMounted(() => {
    const canAsk = !props.enrollment || !['requested', 'accepted'].includes(props.enrollment.status);

    if (signedIn.value && props.acceptsRequests && !props.isOwner && canAsk
        && new URLSearchParams(window.location.search).has('request')) {
        requestOpen.value = true;
    }
});

function sendRequest() {
    requestForm.post(route('classes.request', cls.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            requestOpen.value = false;
            requestForm.reset();
        },
    });
}
</script>

<style scoped src="./public.css"></style>

<style scoped>
.class-page__back {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 16px;
    color: var(--rq-text-link);
    font-size: 14px;
    text-decoration: none;
}

.class-page {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 16px;
    align-items: start;
}

@media (max-width: 860px) {
    .class-page {
        grid-template-columns: minmax(0, 1fr);
    }
}

.class-page__main,
.class-page__side {
    padding: 24px;
    border-radius: var(--rq-radius-card);
}

.class-page__side {
    position: sticky;
    top: calc(var(--rq-topbar-height) + 16px);
}

.class-page__title {
    margin: 0 0 16px;
    font-size: clamp(22px, 3vw, 30px);
    font-weight: 700;
    color: var(--rq-text-heading);
}

.class-page__description {
    line-height: var(--rq-lh-body);
}

.class-page__facts {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin: 0 0 20px;
}

.class-page__facts dt {
    font-size: var(--rq-text-caption);
    color: var(--rq-text-subtle);
}

.class-page__facts dd {
    margin: 2px 0 0;
    color: var(--rq-text-body);
}

.class-page__price {
    font-family: var(--rq-font-display);
    font-size: 22px;
    font-weight: 700;
    color: var(--rq-text-number) !important;
}

.class-page__mine {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.class-page__note {
    margin: 0;
    font-size: 14px;
    color: var(--rq-text-muted);
    text-align: center;
}
</style>

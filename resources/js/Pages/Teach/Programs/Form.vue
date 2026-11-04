<template>
    <Head :title="item ? item.name : $t('teach.programs.create_title')" />

    <PageIntro
        :title="item ? item.name : $t('teach.programs.create_title')"
        :description="item ? '' : $t('teach.programs.form.lead')"
    >
        <template v-if="item" #actions>
            <div class="d-flex flex-wrap ga-2">
                <v-btn
                    v-if="item.is_active"
                    class="text-none"
                    variant="tonal"
                    color="primary"
                    prepend-icon="tabler:plus"
                    @click="router.visit(route('teach.classes.create', { program: item.id }))"
                >
                    {{ $t('teach.programs.new_class') }}
                </v-btn>
                <v-btn
                    v-if="item.public_url && item.image_url && item.is_active && item.is_published"
                    class="text-none"
                    variant="text"
                    color="primary"
                    prepend-icon="tabler:external-link"
                    :href="item.public_url"
                    target="_blank"
                    rel="noopener"
                >
                    {{ $t('teach.programs.view') }}
                </v-btn>
                <v-menu location="bottom end">
                    <template #activator="{ props: menuProps }">
                        <v-btn v-bind="menuProps" icon variant="text" size="small" :aria-label="$t('teach.money.more')">
                            <v-icon icon="tabler:dots-vertical" />
                        </v-btn>
                    </template>
                    <v-list density="compact">
                        <v-list-item class="cf-menu-item" base-color="error" @click="deleteDialog = true">
                            <v-list-item-title>{{ $t('teach.programs.delete') }}</v-list-item-title>
                            <template #append><v-icon icon="tabler:trash" size="16" /></template>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </div>
        </template>
    </PageIntro>

    <v-alert v-if="form.errors.program" type="error" variant="tonal" class="mb-4" rounded="lg">{{ form.errors.program }}</v-alert>

    <div class="cf">
        <form id="program-form" class="cf__main" @submit.prevent="submit">
            <!-- The steps: one at a time on a new program; tabs to open in any order on an existing one -->
            <nav class="cf-steps" :class="{ 'cf-steps--tabs': item }" :style="{ '--cf-steps': steps.length }" :aria-label="$t('teach.programs.wizard.label')">
                <button
                    v-for="(entry, index) in steps"
                    :key="entry.key"
                    type="button"
                    class="cf-step"
                    :class="{
                        'cf-step--on': step === index,
                        'cf-step--done': stepDone(index),
                        'cf-step--error': stepHasErrors(index),
                    }"
                    :disabled="!canOpen(index)"
                    :aria-current="step === index ? 'step' : undefined"
                    @click="goTo(index)"
                >
                    <span class="cf-step__num">
                        <v-icon v-if="stepHasErrors(index)" icon="tabler:alert-triangle" size="15" />
                        <v-icon v-else-if="stepDone(index)" icon="tabler:check" size="16" />
                        <template v-else>{{ index + 1 }}</template>
                    </span>
                    <span class="cf-step__text">
                        <small>{{ $t('teach.classes.wizard.step', { n: index + 1 }) }}</small>
                        <strong>{{ $t(`teach.programs.wizard.steps.${entry.key}`) }}</strong>
                    </span>
                </button>
            </nav>

            <div class="cf__fieldset">
                <!-- Step 1. The program's name -->
                <section v-show="step === 0" id="pf-basics" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:pencil" size="16" /></span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.programs.form.basics') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.programs.form.basics_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <FormPlainTextField
                            v-model="form.name"
                            :label="$t('teach.programs.fields.name')"
                            :placeholder="$t('teach.programs.fields.name_hint')"
                            required
                            :field-attrs="{ name: 'name', maxlength: 120 }"
                            :error-messages="form.errors.name"
                        />
                    </div>
                </section>

                <!-- Step 2. Content: description, the three lists and the video -->
                <section v-show="step === 1" id="pf-content" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:file-text" size="16" /></span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.programs.form.content') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.programs.form.content_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <CourseContentFields :form="form" :description-label="$t('teach.programs.fields.description')" />
                    </div>
                </section>

                <!-- Step 3. The promotional image or video (one of them), then status and visibility -->
                <section v-show="step === 2" id="pf-image" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:photo" size="16" /></span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.programs.fields.image') }}</h2>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <PromoMediaChoice v-model="media" :form="form">
                            <template #image>
                                <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/webp" class="d-none" @change="pickImage">
                                <div v-if="imagePreview" class="cf-image">
                                    <img :src="imagePreview" alt="">
                                    <div class="cf-image__tools">
                                        <v-btn class="text-none" variant="flat" color="surface" size="small" prepend-icon="tabler:upload" @click="imageInput.click()">
                                            {{ $t('teach.programs.fields.image_change') }}
                                        </v-btn>
                                        <v-btn class="text-none" variant="flat" color="surface" size="small" :aria-label="$t('teach.programs.fields.image_remove')" @click="clearImage">
                                            <v-icon icon="tabler:trash" color="error" />
                                        </v-btn>
                                    </div>
                                </div>
                                <button v-else type="button" class="cf-drop" @click="imageInput.click()">
                                    <span class="rq-icon-tile cf-drop__tile"><v-icon icon="tabler:photo" /></span>
                                    <strong>{{ $t('teach.programs.fields.image_upload') }}</strong>
                                    <small>{{ $t('teach.programs.fields.image_hint') }}</small>
                                </button>
                                <small v-if="form.errors.image" class="text-error d-block mt-2">{{ form.errors.image }}</small>
                            </template>
                        </PromoMediaChoice>
                    </div>
                </section>

                <section v-show="step === 2" id="pf-status" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:eye" size="16" /></span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.programs.form.status') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.programs.form.status_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body pf-options">
                        <div v-for="option in options" :key="option.key" class="cf-option" :class="{ 'cf-option--on': form[option.key] }">
                            <div class="cf-option__head">
                                <span class="rq-icon-tile cf-option__tile" :class="option.tile"><v-icon :icon="option.icon" /></span>
                                <div class="cf-option__text">
                                    <strong>{{ $t(`teach.programs.fields.${option.key}`) }}</strong>
                                    <small>{{ $t(`teach.programs.fields.${option.key}_hint`) }}</small>
                                </div>
                                <v-switch
                                    v-model="form[option.key]"
                                    color="primary"
                                    density="compact"
                                    hide-details
                                    inset
                                    class="cf-option__switch"
                                    :aria-label="$t(`teach.programs.fields.${option.key}`)"
                                />
                            </div>
                            <p v-if="option.key === 'is_published' && form.is_published && !hasMedia" class="pf-options__warn">
                                <v-icon icon="tabler:alert-triangle" size="16" />
                                {{ $t('teach.programs.fields.image_required') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="cf-nav">
                <v-btn
                    v-if="step > 0"
                    class="text-none"
                    variant="text"
                    color="primary"
                    prepend-icon="tabler:arrow-right"
                    @click="goTo(step - 1)"
                >
                    {{ $t('teach.classes.wizard.back') }}
                </v-btn>
                <v-spacer />
                <v-btn
                    v-if="step < last"
                    class="text-none"
                    variant="flat"
                    color="primary"
                    append-icon="tabler:arrow-left"
                    @click="next"
                >
                    {{ $t('teach.classes.wizard.next', { name: $t(`teach.programs.wizard.steps.${steps[step + 1].key}`) }) }}
                </v-btn>
                <v-btn
                    v-else-if="!item"
                    type="submit"
                    class="text-none"
                    variant="flat"
                    color="primary"
                    prepend-icon="tabler:check"
                    :loading="form.processing"
                >
                    {{ $t('teach.programs.create') }}
                </v-btn>
            </div>
        </form>

        <!-- Side panel: a live preview, the save button, and what is still missing -->
        <aside class="cf__side">
            <div class="cf-side">
                <div class="cf-preview">
                    <div class="cf-preview__media">
                        <img v-if="imagePreview" :src="imagePreview" alt="">
                        <v-icon v-else :icon="hasVideo ? 'tabler:player-play' : 'tabler:photo'" size="32" />
                    </div>
                    <div class="cf-preview__body pf-info">
                        <strong class="pf-info__title">{{ form.name || $t('teach.programs.form.untitled') }}</strong>
                        <span class="pf-info__state" :class="`pf-info__state--${visibility.tone}`">
                            <v-icon :icon="visibility.icon" size="16" />
                            {{ visibility.text }}
                        </span>
                        <div v-if="item" class="pf-info__row">
                            <span class="pf-info__label">
                                <v-icon icon="tabler:school" size="17" />
                                {{ $t('teach.programs.form.classes_label') }}
                            </span>
                            <strong class="pf-info__value rq-number">{{ item.classes_count }}</strong>
                        </div>
                    </div>
                </div>

                <v-btn
                    v-if="item"
                    type="submit"
                    form="program-form"
                    class="text-none"
                    variant="flat"
                    color="primary"
                    size="large"
                    block
                    :loading="form.processing"
                >
                    {{ $t('teach.programs.save') }}
                </v-btn>
                <div class="cf-ready">
                    <div class="cf-ready__head">
                        <strong>{{ $t('teach.programs.form.ready_title') }}</strong>
                        <span class="rq-number">{{ readyDone }}/{{ readiness.length }}</span>
                    </div>
                    <span class="cf-ready__bar" aria-hidden="true"><span :style="{ width: `${Math.round((readyDone / readiness.length) * 100)}%` }" /></span>
                    <ul class="cf-ready__list">
                        <li v-for="entry in readiness" :key="entry.key" :class="{ 'cf-ready__item--done': entry.done }">
                            <button type="button" @click="openSection(entry.section)">
                                <v-icon :icon="entry.done ? 'tabler:circle-check' : 'tabler:circle'" size="18" />
                                {{ $t(`teach.programs.form.ready.${entry.key}`) }}
                            </button>
                        </li>
                    </ul>
                    <p class="cf-ready__note">{{ $t('teach.programs.form.ready_note') }}</p>
                </div>
            </div>
        </aside>
    </div>

    <ConfirmDialog
        v-if="item"
        v-model="deleteDialog"
        :title="$t('teach.programs.delete_title')"
        :text="$t('teach.programs.delete_text', { name: item.name })"
        :confirm-label="$t('teach.programs.delete_confirm_button')"
        :loading="deleting"
        @confirm="destroy"
    />

    <CreatedDialog
        v-if="item"
        v-model="createdOpen"
        :title="item.name"
        :heading="$t('teach.programs.created.title')"
        :text="$t('teach.programs.created.text')"
        :open-label="$t('teach.programs.created.open')"
        :another-label="$t('teach.programs.created.another')"
        :another-href="route('teach.programs.create')"
    />

    <!-- Phones: the save button stays in reach at the bottom -->
    <div class="cf-bar">
        <v-btn
            v-if="!item && step > 0"
            icon
            variant="text"
            size="small"
            color="primary"
            :aria-label="$t('teach.classes.wizard.back')"
            @click="goTo(step - 1)"
        >
            <v-icon icon="tabler:arrow-right" />
        </v-btn>
        <span class="cf-bar__status">
            {{ item
                ? $t('teach.programs.form.ready_short', { done: readyDone, total: readiness.length })
                : $t('teach.classes.wizard.bar', { n: step + 1, total: steps.length, name: $t(`teach.programs.wizard.steps.${steps[step].key}`) }) }}
        </span>
        <v-btn
            v-if="!item && step < last"
            class="text-none"
            variant="flat"
            color="primary"
            append-icon="tabler:arrow-left"
            @click="next"
        >
            {{ $t('teach.classes.wizard.next_short') }}
        </v-btn>
        <v-btn v-else type="submit" form="program-form" class="text-none" variant="flat" color="primary" :loading="form.processing">
            {{ item ? $t('teach.programs.save') : $t('teach.programs.create') }}
        </v-btn>
    </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PageIntro from '@/Components/PageIntro.vue';
import CourseContentFields from '@/Components/CourseContentFields.vue';
import PromoMediaChoice from '@/Components/PromoMediaChoice.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import CreatedDialog from '@/Components/CreatedDialog.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    item: { type: Object, default: null },
    // The program was saved a moment ago: show the «تم إنشاء البرنامج» dialog.
    justCreated: { type: Boolean, default: false },
});

const createdOpen = ref(props.justCreated);

const { t } = useI18n();

const form = useForm({
    name: props.item?.name || '',
    description: props.item?.description || '',
    learn: [...(props.item?.learn || [])],
    requirements: [...(props.item?.requirements || [])],
    audience: [...(props.item?.audience || [])],
    promo_video_url: props.item?.promo_video_url || '',
    is_active: props.item ? props.item.is_active : true,
    is_published: props.item ? props.item.is_published : false,
    is_featured: props.item ? props.item.is_featured : false,
    image: null,
    remove_image: false,
});

// The poster: the saved one, or the file just chosen (shown before saving).
const imageInput = ref(null);
const chosenPreview = ref(null);
const imagePreview = computed(() => (media.value !== 'image' ? null : (chosenPreview.value || (form.remove_image ? null : props.item?.image_url || null))));

// The promotional image or video, one of them, never both ('' = neither yet). A program that has an
// image keeps it; an old one with both keeps the image and drops the video when saved.
const media = ref(props.item?.image_url ? 'image' : (props.item?.promo_video_url ? 'video' : ''));

if (media.value === 'image') {
    form.promo_video_url = '';
}

const hasVideo = computed(() => media.value === 'video' && form.promo_video_url.trim() !== '');
const hasMedia = computed(() => Boolean(imagePreview.value) || hasVideo.value);

watch(media, (value) => {
    if (value === 'image') {
        form.promo_video_url = '';
        form.remove_image = false;
    } else {
        // A video, or nothing: the image goes (a saved one is removed on save).
        clearImage();

        if (value === '') {
            form.promo_video_url = '';
        }
    }
});

function pickImage(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    if (chosenPreview.value) {
        URL.revokeObjectURL(chosenPreview.value);
    }

    form.image = file;
    form.remove_image = false;
    chosenPreview.value = URL.createObjectURL(file);
    event.target.value = '';
}

function clearImage() {
    if (chosenPreview.value) {
        URL.revokeObjectURL(chosenPreview.value);
    }

    form.image = null;
    form.remove_image = Boolean(props.item?.image_url);
    chosenPreview.value = null;
}

// ---------- Status switches, and the side panel ----------
const options = [
    { key: 'is_active', icon: 'tabler:player-play', tile: 'rq-icon-tile--green' },
    { key: 'is_published', icon: 'tabler:eye', tile: '' },
    { key: 'is_featured', icon: 'tabler:star', tile: 'rq-icon-tile--amber' },
];

const visibility = computed(() => {
    if (!form.is_active) {
        return { icon: 'tabler:player-pause', text: t('teach.programs.inactive'), tone: 'paused' };
    }

    return form.is_published && hasMedia.value
        ? { icon: 'tabler:eye', text: t('teach.programs.form.shown'), tone: 'on' }
        : { icon: 'tabler:eye-off', text: t('teach.programs.form.not_shown'), tone: 'off' };
});

const readiness = computed(() => [
    { key: 'name', section: 'pf-basics', done: form.name.trim().length >= 3 },
    { key: 'description', section: 'pf-content', done: form.description.trim().length >= DESCRIPTION_MIN },
    { key: 'learn', section: 'pf-content', done: form.learn.some((entry) => entry.trim() !== '') },
    { key: 'image', section: 'pf-image', done: hasMedia.value },
]);
const readyDone = computed(() => readiness.value.filter((entry) => entry.done).length);

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ---------- Steps ----------
// A new program is filled one step at a time («التالي» checks the step first); an existing one
// opens any step as a tab. Each field belongs to one step, so a server error opens its step.
const steps = [
    { key: 'name', sections: ['pf-basics'], fields: ['name'] },
    { key: 'content', sections: ['pf-content'], fields: ['description', 'learn', 'requirements', 'audience'] },
    { key: 'publish', sections: ['pf-image', 'pf-status'], fields: ['promo_video_url', 'image', 'remove_image', 'is_active', 'is_published', 'is_featured'] },
];
const last = steps.length - 1;
const step = ref(0);
const reached = ref(props.item ? last : 0);

const stepOf = (field) => steps.findIndex((entry) => entry.fields.includes(field.split('.')[0]));
const stepHasErrors = (index) => Object.keys(form.errors).some((field) => stepOf(field) === index);
const canOpen = (index) => index <= reached.value;

// The shortest description of a program, and the shortest point in its lists (as the server).
const DESCRIPTION_MIN = 20;
const ITEM_MIN = 3;

// What a step still lacks, as { field: message } (the server checks everything again).
function missing(index) {
    const need = {};

    if (index === 0 && form.name.trim().length < 3) {
        need.name = t('teach.programs.wizard.name_short');
    }

    if (index === 1) {
        if (!form.description.trim()) {
            need.description = t('teach.classes.wizard.required');
        } else if (form.description.trim().length < DESCRIPTION_MIN) {
            need.description = t('teach.programs.wizard.description_short', { min: DESCRIPTION_MIN });
        }

        // The lists may stay empty, but a point that is written needs at least three letters.
        ['learn', 'requirements', 'audience'].forEach((list) => {
            form[list].forEach((entry, index) => {
                if (entry.trim() !== '' && entry.trim().length < ITEM_MIN) {
                    need[`${list}.${index}`] = t('teach.programs.wizard.item_short', { min: ITEM_MIN });
                }
            });
        });
    }

    return need;
}

const stepDone = (index) => index !== step.value && index <= reached.value && !stepHasErrors(index)
    && Object.keys(missing(index)).length === 0;

function scrollToErrors() {
    nextTick(() => document.querySelector('#program-form .v-messages__message, #program-form small.text-error')
        ?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
}

function goTo(index, force = false) {
    if (index < 0 || index > last || (!force && !canOpen(index))) {
        return;
    }

    step.value = index;
    reached.value = Math.max(reached.value, index);
    nextTick(() => document.getElementById('program-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

// «التالي»: the step's own checks first, then the next step.
function next() {
    const need = missing(step.value);

    form.clearErrors(...steps[step.value].fields);

    if (Object.keys(need).length > 0) {
        form.setError(need);
        scrollToErrors();

        return;
    }

    goTo(step.value + 1, true);
}

// Saving runs every step's checks (an existing program opens any step as a tab), then saves.
function submit() {
    if (!props.item && step.value < last) {
        next();

        return;
    }

    const need = steps.reduce((all, _, index) => ({ ...all, ...missing(index) }), {});

    if (Object.keys(need).length > 0) {
        form.clearErrors();
        form.setError(need);
        openFirstError();
        nextTick(scrollToErrors);

        return;
    }

    save();
}

function openFirstError() {
    nextTick(() => {
        const first = steps.findIndex((_, index) => stepHasErrors(index));

        if (first !== -1 && first !== step.value) {
            goTo(first, true);
        }
    });
}

// The readiness list opens the step a section is on, then scrolls to it.
function openSection(id) {
    goTo(steps.findIndex((entry) => entry.sections.includes(id)), true);
    nextTick(() => scrollTo(id));
}

function save() {
    const send = (data) => {
        const { image, ...rest } = data;

        return { ...rest, ...(image ? { image } : {}) };
    };
    const saved = () => {
        form.image = null;
        form.remove_image = false;
        chosenPreview.value = null;
    };

    // A file needs a multipart body, and PHP only reads one on POST: update through _method.
    if (props.item) {
        form.transform((data) => ({ ...send(data), _method: 'put' }))
            .post(route('teach.programs.update', props.item.id), { preserveScroll: true, forceFormData: true, onSuccess: saved, onError: openFirstError });
    } else {
        // Inertia's post keeps the page's state; only keep it when the form comes back with errors.
        form.transform(send).post(route('teach.programs.store'), {
            forceFormData: true,
            preserveState: (page) => Object.keys(page.props.errors || {}).length > 0,
            onSuccess: saved,
            onError: openFirstError,
        });
    }
}

// «حذف»: asks first (a program with classes is refused by the server, and the reason is shown at the top).
const deleteDialog = ref(false);
const deleting = ref(false);

function destroy() {
    router.delete(route('teach.programs.destroy', props.item.id), {
        onStart: () => { deleting.value = true; },
        onError: (errors) => {
            form.setError('program', errors.program);
        },
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
        },
    });
}
</script>

<style scoped src="../teach.css"></style>
<style scoped src="../courseForm.css"></style>

<style scoped>
/* The text under the preview image: a clear title, the state as a chip, the classes as a row */
.pf-info {
    gap: 12px;
    padding: 16px 16px 18px;
}

.pf-info__title {
    font-family: var(--rq-font-display);
    font-size: 17px;
    font-weight: 700;
    line-height: 1.5;
    color: var(--rq-text-heading);
    overflow-wrap: anywhere;
}

.pf-info__state {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: 6px;
    padding: 4px 12px;
    border-radius: var(--rq-radius-pill);
    font-size: 13px;
    font-weight: 600;
}

.pf-info__state--on {
    background: var(--rq-success-tint);
    color: var(--rq-success);
}

.pf-info__state--off {
    background: var(--rq-surface-tint);
    color: var(--rq-text-body);
}

.pf-info__state--paused {
    background: var(--rq-warning-tint);
    color: var(--rq-warning);
}

.pf-info__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--rq-line-soft);
    font-size: 14px;
    color: var(--rq-text-body);
}

.pf-info__label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.pf-info__value {
    font-family: var(--rq-font-display);
    font-size: 18px;
    color: var(--rq-text-heading);
}

.pf-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.pf-options__warn {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    margin: 0;
    padding: 0 16px 14px;
    font-size: 12px;
    line-height: 1.6;
    color: var(--rq-warning);
}
</style>

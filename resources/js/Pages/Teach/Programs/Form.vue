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
                        <v-list-item :title="$t('teach.programs.delete')" base-color="error" @click="destroy" />
                    </v-list>
                </v-menu>
            </div>
        </template>
    </PageIntro>

    <v-alert v-if="form.errors.program" type="error" variant="tonal" class="mb-4" rounded="lg">{{ form.errors.program }}</v-alert>

    <div class="cf">
        <form id="program-form" class="cf__main" @submit.prevent="save">
            <div class="cf__fieldset">
                <!-- 1. Basics: the program's name -->
                <section id="pf-basics" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">1</span>
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

                <!-- 2. Content: description, the three lists and the video -->
                <section id="pf-content" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">2</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.programs.form.content') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.programs.form.content_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <CourseContentFields :form="form" :description-label="$t('teach.programs.fields.description')" />
                    </div>
                </section>

                <!-- 3. Promotional image -->
                <section id="pf-image" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">3</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.programs.fields.image') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.programs.form.image_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
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
                    </div>
                </section>

                <!-- 4. Status and visibility -->
                <section id="pf-status" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">4</span>
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
                            <p v-if="option.key === 'is_published' && form.is_published && !imagePreview" class="pf-options__warn">
                                <v-icon icon="tabler:alert-triangle" size="16" />
                                {{ $t('teach.programs.fields.image_required') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </form>

        <!-- Side panel: a live preview, the save button, and what is still missing -->
        <aside class="cf__side">
            <div class="cf-side">
                <div class="cf-preview">
                    <div class="cf-preview__media">
                        <img v-if="imagePreview" :src="imagePreview" alt="">
                        <v-icon v-else icon="tabler:photo" size="32" />
                    </div>
                    <div class="cf-preview__body">
                        <span class="cf-preview__kicker">{{ $t('teach.programs.form.kicker') }}</span>
                        <strong class="cf-preview__title">{{ form.name || $t('teach.programs.form.untitled') }}</strong>
                        <span class="cf-preview__meta">
                            <v-icon :icon="visibility.icon" size="15" />
                            {{ visibility.text }}
                        </span>
                        <span v-if="item" class="cf-preview__meta">
                            <v-icon icon="tabler:school" size="15" />
                            {{ $t('teach.programs.classes_note', { count: item.classes_count }) }}
                        </span>
                    </div>
                </div>

                <v-btn
                    type="submit"
                    form="program-form"
                    class="text-none"
                    variant="flat"
                    color="primary"
                    size="large"
                    block
                    :loading="form.processing"
                >
                    {{ item ? $t('teach.programs.save') : $t('teach.programs.create') }}
                </v-btn>
                <p v-if="!item" class="cf-side__hint">{{ $t('teach.programs.form.hidden_note') }}</p>
                <div class="cf-ready">
                    <div class="cf-ready__head">
                        <strong>{{ $t('teach.programs.form.ready_title') }}</strong>
                        <span class="rq-number">{{ readyDone }}/{{ readiness.length }}</span>
                    </div>
                    <span class="cf-ready__bar" aria-hidden="true"><span :style="{ width: `${Math.round((readyDone / readiness.length) * 100)}%` }" /></span>
                    <ul class="cf-ready__list">
                        <li v-for="entry in readiness" :key="entry.key" :class="{ 'cf-ready__item--done': entry.done }">
                            <button type="button" @click="scrollTo(entry.section)">
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

    <!-- Phones: the save button stays in reach at the bottom -->
    <div class="cf-bar">
        <span class="cf-bar__status">{{ $t('teach.programs.form.ready_short', { done: readyDone, total: readiness.length }) }}</span>
        <v-btn type="submit" form="program-form" class="text-none" variant="flat" color="primary" :loading="form.processing">
            {{ item ? $t('teach.programs.save') : $t('teach.programs.create') }}
        </v-btn>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PageIntro from '@/Components/PageIntro.vue';
import CourseContentFields from '@/Components/CourseContentFields.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    item: { type: Object, default: null },
});

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
const imagePreview = computed(() => chosenPreview.value || (form.remove_image ? null : props.item?.image_url || null));

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
        return { icon: 'tabler:player-pause', text: t('teach.programs.inactive') };
    }

    return form.is_published && imagePreview.value
        ? { icon: 'tabler:eye', text: t('teach.programs.form.shown') }
        : { icon: 'tabler:eye-off', text: t('teach.programs.form.not_shown') };
});

const readiness = computed(() => [
    { key: 'name', section: 'pf-basics', done: form.name.trim().length >= 3 },
    { key: 'description', section: 'pf-content', done: form.description.trim() !== '' },
    { key: 'learn', section: 'pf-content', done: form.learn.some((entry) => entry.trim() !== '') },
    { key: 'image', section: 'pf-image', done: Boolean(imagePreview.value) },
]);
const readyDone = computed(() => readiness.value.filter((entry) => entry.done).length);

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
            .post(route('teach.programs.update', props.item.id), { preserveScroll: true, forceFormData: true, onSuccess: saved });
    } else {
        form.transform(send).post(route('teach.programs.store'), { forceFormData: true, onSuccess: saved });
    }
}

function destroy() {
    if (window.confirm(t('teach.programs.delete_confirm'))) {
        router.delete(route('teach.programs.destroy', props.item.id), {
            onError: (errors) => {
                form.setError('program', errors.program);
            },
        });
    }
}
</script>

<style scoped src="../teach.css"></style>
<style scoped src="../courseForm.css"></style>

<style scoped>
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

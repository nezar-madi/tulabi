<template>
    <Head :title="$t('teach.profile.title')" />

    <PageIntro :title="$t('teach.profile.title')" :description="$t('teach.profile.subtitle')">
        <template #actions>
        <v-btn
            v-if="profile.public_url"
            class="rq-btn"
            variant="tonal"
            color="primary"
            :href="profile.public_url"
            target="_blank"
            rel="noopener"
        >
            <v-icon icon="tabler:external-link" start />
            {{ $t('teach.profile.preview') }}
        </v-btn>
        </template>
    </PageIntro>

    <div class="profile">
        <section class="rq-card teach-section profile__photo">
            <h2 class="teach-section__title">{{ $t('teach.profile.photo') }}</h2>
            <div class="profile__avatar">
                <img v-if="profile.photo_url" :src="profile.photo_url" :alt="profile.display_name">
                <span v-else>{{ initial }}</span>
            </div>
            <p class="profile__hint">{{ $t('teach.profile.photo_hint') }}</p>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="d-none" @change="upload">
            <div class="d-flex flex-wrap ga-2 justify-center">
                <v-btn class="rq-btn rq-btn--primary" :loading="photoForm.processing" @click="fileInput.click()">
                    <v-icon icon="tabler:upload" start />
                    {{ $t('teach.profile.upload') }}
                </v-btn>
                <v-btn v-if="profile.photo_url" class="rq-btn" variant="text" color="error" @click="removePhoto">
                    {{ $t('teach.profile.remove_photo') }}
                </v-btn>
            </div>
            <small v-if="photoForm.errors.photo" class="text-error d-block mt-2">{{ photoForm.errors.photo }}</small>
        </section>

        <section class="rq-card teach-section profile__form">
            <form @submit.prevent="save">
                <FormPlainTextField
                    v-model="form.slug"
                    :label="$t('teach.profile.slug')"
                    placeholder="ahmed-math"
                    :field-attrs="{ name: 'slug', dir: 'ltr', autocapitalize: 'off', spellcheck: 'false' }"
                    :error-messages="form.errors.slug"
                    class="mb-1"
                />
                <p class="teach-hint mt-0" dir="ltr">{{ baseUrl }}<strong>{{ form.slug || '…' }}</strong></p>
                <p class="teach-hint">{{ $t('teach.profile.slug_hint') }}</p>

                <FormPlainTextField
                    v-model="form.display_name"
                    :label="$t('teach.profile.display_name')"
                    hide-placeholder
                    :field-attrs="{ name: 'display_name' }"
                    :error-messages="form.errors.display_name"
                    class="mb-3"
                />
                <FormPlainTextField
                    v-model="form.specialty"
                    :label="$t('fields.specialty')"
                    :placeholder="$t('fields.specialty_hint')"
                    :field-attrs="{ name: 'specialty' }"
                    :error-messages="form.errors.specialty"
                    class="mb-3"
                />
                <FormPlainTextField
                    v-model="form.city"
                    :label="$t('fields.city')"
                    hide-placeholder
                    :field-attrs="{ name: 'city' }"
                    :error-messages="form.errors.city"
                    class="mb-3"
                />
                <!-- «نبذة» as sections: «من أنا» first, then whatever the instructor adds -->
                <p class="form-plain-field__label font-weight-medium mb-1">{{ $t('teach.profile.about') }}</p>
                <p class="teach-hint mt-0">{{ $t('teach.profile.about_hint') }}</p>
                <div v-for="(section, index) in form.sections" :key="section.uid" class="about-section">
                    <div class="about-section__head">
                        <FormPlainTextField
                            v-model="section.title"
                            :label="$t('teach.profile.section_title')"
                            hide-placeholder
                            :field-attrs="{ name: `sections.${index}.title`, maxlength: 60 }"
                            :error-messages="form.errors[`sections.${index}.title`]"
                            class="about-section__title"
                        />
                        <div class="about-section__tools">
                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                :disabled="index === 0"
                                :aria-label="$t('teach.profile.section_up')"
                                @click="move(index, -1)"
                            >
                                <v-icon icon="tabler:chevron-up" />
                            </v-btn>
                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                :disabled="index === form.sections.length - 1"
                                :aria-label="$t('teach.profile.section_down')"
                                @click="move(index, 1)"
                            >
                                <v-icon icon="tabler:chevron-down" />
                            </v-btn>
                            <v-btn
                                v-if="index > 0"
                                icon
                                variant="text"
                                size="small"
                                color="error"
                                :aria-label="$t('teach.profile.section_remove')"
                                @click="removeSection(index)"
                            >
                                <v-icon icon="tabler:trash" />
                            </v-btn>
                        </div>
                    </div>
                    <FormPlainRichText
                        v-model="section.body"
                        :label="$t('teach.profile.section_body')"
                        :placeholder="index === 0 ? $t('fields.bio_hint') : $t('teach.profile.section_body_hint')"
                        :error-messages="form.errors[`sections.${index}.body`]"
                    />
                </div>
                <div v-if="form.sections.length < maxSections" class="about-add">
                    <v-btn class="rq-btn" variant="tonal" color="primary" @click="addSection('')">
                        <v-icon icon="tabler:plus" start />
                        {{ $t('teach.profile.add_section') }}
                    </v-btn>
                    <button
                        v-for="title in freeSuggestions"
                        :key="title"
                        type="button"
                        class="about-add__chip"
                        @click="addSection(title)"
                    >
                        + {{ title }}
                    </button>
                </div>
                <small v-if="form.errors.sections" class="text-error d-block mb-3">{{ form.errors.sections }}</small>
                <div class="mb-3" />
                <FormPlainTextField
                    v-model="form.whatsapp"
                    :label="$t('teach.profile.whatsapp')"
                    :placeholder="$t('fields.phone_hint')"
                    :field-attrs="{ name: 'whatsapp', inputmode: 'tel', dir: 'ltr' }"
                    :error-messages="form.errors.whatsapp"
                    class="mb-1"
                />
                <p class="teach-hint mt-1">{{ $t('teach.profile.whatsapp_hint') }}</p>

                <p class="form-plain-field__label font-weight-medium mb-1">{{ $t('teach.profile.links') }}</p>
                <p class="teach-hint mt-0">{{ $t('teach.profile.links_hint') }}</p>
                <div v-for="(link, index) in form.links" :key="index" class="d-flex align-start ga-2">
                    <FormPlainTextField
                        v-model="form.links[index]"
                        placeholder="https://"
                        :field-attrs="{ name: `links.${index}`, dir: 'ltr', inputmode: 'url' }"
                        :error-messages="form.errors[`links.${index}`]"
                        class="flex-grow-1 mb-2"
                    />
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        class="mt-2"
                        :aria-label="$t('teach.profile.remove_link')"
                        @click="form.links.splice(index, 1)"
                    >
                        <v-icon icon="tabler:x" />
                    </v-btn>
                </div>
                <v-btn
                    v-if="form.links.length < 5"
                    class="rq-btn"
                    variant="text"
                    color="primary"
                    @click="form.links.push('')"
                >
                    <v-icon icon="tabler:plus" start />
                    {{ $t('teach.profile.add_link') }}
                </v-btn>

                <div class="mt-6">
                    <v-btn type="submit" class="rq-btn rq-btn--primary" size="large" :loading="form.processing">
                        {{ $t('teach.profile.save') }}
                    </v-btn>
                </div>
            </form>
        </section>
    </div>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import FormPlainRichText from '@/Components/formFields/FormPlainRichText.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    baseUrl: { type: String, required: true },
    sectionSuggestions: { type: Array, default: () => [] },
});

const fileInput = ref(null);
const initial = computed(() => (props.profile.display_name || '؟').trim().charAt(0));

const maxSections = 8;
let uidCounter = 0;
const nextUid = () => {
    uidCounter += 1;

    return uidCounter;
};

const form = useForm({
    slug: props.profile.slug || '',
    display_name: props.profile.display_name,
    specialty: props.profile.specialty,
    city: props.profile.city,
    sections: (props.profile.sections || []).map((section) => ({ uid: nextUid(), ...section })),
    whatsapp: props.profile.whatsapp || '',
    links: [...(props.profile.links || [])],
});

const photoForm = useForm({ photo: null });

// Titles offered as one-click additions, minus the ones already used.
const freeSuggestions = computed(() => (props.sectionSuggestions || [])
    .filter((title) => !form.sections.some((section) => section.title.trim() === title)));

function addSection(title) {
    form.sections.push({ uid: nextUid(), title, body: '' });
}

function removeSection(index) {
    form.sections.splice(index, 1);
}

function move(index, step) {
    const target = index + step;

    if (target < 0 || target >= form.sections.length) {
        return;
    }

    const [section] = form.sections.splice(index, 1);
    form.sections.splice(target, 0, section);
}

function save() {
    // The key that keeps each editor with its section is for the page only.
    form.transform((data) => ({
        ...data,
        sections: data.sections.map(({ title, body }) => ({ title, body })),
    })).put(route('teach.profile.update'), { preserveScroll: true });
}

function upload(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    photoForm.photo = file;
    photoForm.post(route('teach.profile.photo'), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            event.target.value = '';
        },
    });
}

function removePhoto() {
    router.delete(route('teach.profile.photo.delete'), { preserveScroll: true });
}
</script>

<style scoped src="./teach.css"></style>

<style scoped>
.profile__lead {
    margin: 0 0 20px;
    color: var(--rq-text-muted);
}

.profile {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

@media (max-width: 820px) {
    .profile {
        grid-template-columns: minmax(0, 1fr);
    }
}

.profile__photo {
    text-align: center;
}

.profile__avatar {
    display: grid;
    place-items: center;
    width: 160px;
    height: 160px;
    margin: 0 auto 12px;
    overflow: hidden;
    border-radius: 50%;
    background: var(--rq-icon-tile);
    color: var(--rq-text-heading);
    font-family: var(--rq-font-display);
    font-size: 56px;
    font-weight: 700;
}

.profile__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.about-section {
    margin-bottom: 14px;
    padding: 16px;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-md);
    background: var(--rq-surface-chrome);
}

.about-section__head {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 12px;
}

.about-section__title {
    flex: 1 1 auto;
}

.about-section__tools {
    display: flex;
    flex-shrink: 0;
    margin-top: 26px;
}

.about-add {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.about-add__chip {
    padding: 6px 12px;
    border: 1px dashed var(--rq-line-strong);
    border-radius: var(--rq-radius-pill);
    background: none;
    color: var(--rq-text-body);
    font-family: var(--rq-font-body);
    font-size: 13px;
    cursor: pointer;
}

.about-add__chip:hover {
    border-color: var(--rq-text-link);
    color: var(--rq-text-link);
}

.profile__hint {
    margin: 0 0 12px;
    font-size: var(--rq-text-caption);
    color: var(--rq-text-subtle);
}
</style>

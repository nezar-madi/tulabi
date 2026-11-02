<template>
    <Head :title="item ? item.name : $t('teach.programs.create_title')" />

    <PageIntro
        :title="item ? item.name : $t('teach.programs.create_title')"
        :eyebrow="item ? $t('teach.programs.edit_title') : $t('teach.programs.create_title')"
    >
        <template v-if="item" #actions>
            <div class="d-flex flex-wrap ga-2">
                <v-btn
                    v-if="item.public_url && item.is_active && item.is_published"
                    class="rq-btn"
                    variant="text"
                    color="primary"
                    :href="item.public_url"
                    target="_blank"
                    rel="noopener"
                >
                    <v-icon icon="tabler:external-link" start />
                    {{ $t('teach.programs.view') }}
                </v-btn>
                <v-btn class="rq-btn" variant="text" color="error" @click="destroy">{{ $t('teach.programs.delete') }}</v-btn>
            </div>
        </template>
    </PageIntro>

    <v-alert v-if="form.errors.program" type="error" variant="tonal" class="mb-4" rounded="lg">{{ form.errors.program }}</v-alert>
    <p v-if="item" class="teach-hint mb-3">{{ $t('teach.programs.classes_note', { count: item.classes_count }) }}</p>

    <form @submit.prevent="save">
        <section class="rq-card teach-section">
            <FormPlainTextField
                v-model="form.name"
                :label="$t('teach.programs.fields.name')"
                :placeholder="$t('teach.programs.fields.name_hint')"
                required
                :field-attrs="{ name: 'name', maxlength: 120 }"
                :error-messages="form.errors.name"
                class="mb-3"
            />
            <FormPlainRichText
                v-model="form.description"
                :label="$t('teach.programs.fields.description')"
                :placeholder="$t('teach.programs.fields.description_hint')"
                required
                :error-messages="form.errors.description"
            />
        </section>

        <!-- Poster -->
        <section class="rq-card teach-section">
            <p class="form-plain-field__label font-weight-medium mb-1">{{ $t('teach.programs.fields.image') }}</p>
            <p class="teach-hint">{{ $t('teach.programs.fields.image_hint') }}</p>
            <div class="poster">
                <div class="poster__frame" :class="{ 'poster__frame--empty': !imagePreview }">
                    <img v-if="imagePreview" :src="imagePreview" alt="">
                    <v-icon v-else icon="tabler:photo" size="36" />
                </div>
                <div class="d-flex flex-wrap ga-2">
                    <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/webp" class="d-none" @change="pickImage">
                    <v-btn class="rq-btn" variant="tonal" color="primary" @click="imageInput.click()">
                        <v-icon icon="tabler:upload" start />
                        {{ imagePreview ? $t('teach.programs.fields.image_change') : $t('teach.programs.fields.image_upload') }}
                    </v-btn>
                    <v-btn v-if="imagePreview" class="rq-btn" variant="text" color="error" @click="clearImage">
                        {{ $t('teach.programs.fields.image_remove') }}
                    </v-btn>
                </div>
            </div>
            <small v-if="form.errors.image" class="text-error d-block mt-2">{{ form.errors.image }}</small>
        </section>

        <!-- Status -->
        <section class="rq-card teach-section">
            <FormPlainCheckbox v-model="form.is_active" :label="$t('teach.programs.fields.is_active')" hide-details />
            <p class="teach-hint toggles__hint">{{ $t('teach.programs.fields.is_active_hint') }}</p>
            <FormPlainCheckbox v-model="form.is_published" :label="$t('teach.programs.fields.is_published')" hide-details />
            <FormPlainCheckbox v-model="form.is_featured" :label="$t('teach.programs.fields.is_featured')" hide-details />
            <p class="teach-hint toggles__hint">{{ $t('teach.programs.fields.is_featured_hint') }}</p>
        </section>

        <v-btn type="submit" class="rq-btn rq-btn--primary" size="large" :loading="form.processing">
            {{ item ? $t('teach.programs.save') : $t('teach.programs.create') }}
        </v-btn>
    </form>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PageIntro from '@/Components/PageIntro.vue';
import FormPlainCheckbox from '@/Components/formFields/FormPlainCheckbox.vue';
import FormPlainRichText from '@/Components/formFields/FormPlainRichText.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    item: { type: Object, default: null },
});

const { t } = useI18n();

const form = useForm({
    name: props.item?.name || '',
    description: props.item?.description || '',
    is_active: props.item ? props.item.is_active : true,
    is_published: props.item ? props.item.is_published : true,
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

<style scoped>
.poster {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}

.poster__frame {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 240px;
    max-width: 100%;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-md);
    background: var(--rq-surface-tint);
}

.poster__frame--empty {
    border-style: dashed;
    color: var(--rq-text-subtle);
}

.poster__frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.toggles__hint {
    margin: -6px 0 10px;
    padding-inline-start: 38px;
}
</style>

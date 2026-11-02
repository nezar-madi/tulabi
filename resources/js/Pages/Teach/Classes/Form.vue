<template>
    <Head :title="cls ? cls.title : $t('teach.classes.create_title')" />

    <PageIntro
        :title="cls ? cls.title : $t('teach.classes.create_title')"
        :eyebrow="cls ? $t('teach.classes.edit_title') : $t('teach.classes.create_title')"
    >
        <template #title>
            {{ cls ? cls.title : $t('teach.classes.create_title') }}
            <ClassStatusBadge v-if="cls" :status="cls.status" :ended="cls.ended" class="ms-2 align-middle" />
        </template>

        <!-- Status actions -->
        <template v-if="cls" #actions>
        <div class="d-flex flex-wrap ga-2">
            <Link v-if="cls.status !== 'draft'" :href="route('teach.classes.students', cls.id)" class="text-decoration-none">
                <v-btn class="rq-btn" variant="tonal" color="primary">
                    <v-icon icon="tabler:users" start />
                    {{ $t('teach.students.open') }}
                </v-btn>
            </Link>
            <v-btn
                v-if="publicUrl"
                class="rq-btn"
                variant="text"
                color="primary"
                :href="publicUrl"
                target="_blank"
                rel="noopener"
            >
                <v-icon icon="tabler:external-link" start />
                {{ $t('teach.classes.view') }}
            </v-btn>
            <v-btn v-if="cls.status === 'draft'" class="rq-btn rq-btn--primary" @click="act('publish')">
                {{ $t('teach.classes.publish') }}
            </v-btn>
            <v-btn v-if="cls.status === 'closed'" class="rq-btn rq-btn--primary" @click="act('publish')">
                {{ $t('teach.classes.reopen') }}
            </v-btn>
            <v-btn v-if="cls.status === 'published'" class="rq-btn" variant="outlined" color="primary" @click="act('close')">
                {{ $t('teach.classes.close') }}
            </v-btn>
            <v-btn v-if="cls.status === 'draft'" class="rq-btn" variant="text" color="error" @click="destroy">
                {{ $t('teach.classes.delete') }}
            </v-btn>
            <v-btn
                v-if="cls.status !== 'canceled'"
                class="rq-btn"
                variant="outlined"
                color="error"
                @click="cancelDialog = true"
            >
                {{ $t('teach.classes.cancel') }}
            </v-btn>
        </div>
        </template>
    </PageIntro>

    <v-alert v-if="readOnly" type="warning" variant="tonal" class="mb-4" rounded="lg">
        {{ $t('teach.classes.canceled_note') }}
        <div v-if="cls.cancel_reason" class="mt-1">{{ cls.cancel_reason }}</div>
    </v-alert>

    <form @submit.prevent="save">
        <fieldset :disabled="readOnly" class="class-form">
            <section class="rq-card teach-section">
                <FormPlainTextField
                    v-model="form.title"
                    :label="$t('teach.classes.fields.title')"
                    :placeholder="$t('teach.classes.fields.title_hint')"
                    required
                    :field-attrs="{ name: 'title' }"
                    :error-messages="form.errors.title"
                    class="mb-3"
                />
                <FormPlainRichText
                    v-model="form.description"
                    :label="$t('teach.classes.fields.description')"
                    :placeholder="$t('teach.classes.fields.description_hint')"
                    required
                    :error-messages="form.errors.description"
                />
            </section>

            <!-- Promotional image -->
            <section class="rq-card teach-section">
                <p class="form-plain-field__label font-weight-medium mb-1">{{ $t('teach.classes.fields.image') }}</p>
                <p class="teach-hint">{{ $t('teach.classes.fields.image_hint') }}</p>
                <div class="class-image">
                    <div class="class-image__frame" :class="{ 'class-image__frame--empty': !imagePreview }">
                        <img v-if="imagePreview" :src="imagePreview" alt="">
                        <v-icon v-else icon="tabler:photo" size="36" />
                    </div>
                    <div class="d-flex flex-wrap ga-2">
                        <input
                            ref="imageInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="d-none"
                            @change="pickImage"
                        >
                        <v-btn class="rq-btn" variant="tonal" color="primary" :disabled="readOnly" @click="imageInput.click()">
                            <v-icon icon="tabler:upload" start />
                            {{ imagePreview ? $t('teach.classes.fields.image_change') : $t('teach.classes.fields.image_upload') }}
                        </v-btn>
                        <v-btn v-if="imagePreview" class="rq-btn" variant="text" color="error" :disabled="readOnly" @click="clearImage">
                            {{ $t('teach.classes.fields.image_remove') }}
                        </v-btn>
                    </div>
                </div>
                <small v-if="form.errors.image" class="text-error d-block mt-2">{{ form.errors.image }}</small>
            </section>

            <section class="rq-card teach-section">
                <p class="form-plain-field__label font-weight-medium mb-2">{{ $t('teach.classes.fields.type') }}</p>
                <v-btn-toggle v-model="form.type" mandatory class="mb-4">
                    <v-btn value="in_person">
                        <v-icon icon="tabler:map-pin" start />
                        {{ $t('class.type.in_person') }}
                    </v-btn>
                    <v-btn value="online">
                        <v-icon icon="tabler:video" start />
                        {{ $t('class.type.online') }}
                    </v-btn>
                </v-btn-toggle>

                <template v-if="form.type === 'in_person'">
                    <FormPlainTextField
                        v-model="form.map_url"
                        :label="$t('teach.classes.fields.map_url')"
                        placeholder="https://maps.app.goo.gl/..."
                        required
                        :field-attrs="{ name: 'map_url', dir: 'ltr', inputmode: 'url' }"
                        :error-messages="form.errors.map_url"
                        class="mb-3"
                    />
                    <p class="teach-hint mb-3">{{ $t('teach.classes.fields.map_url_hint') }}</p>
                    <FormPlainTextField
                        v-model="form.location"
                        :label="`${$t('teach.classes.fields.location')} (${$t('common.optional')})`"
                        :placeholder="$t('teach.classes.fields.location_hint')"
                        :field-attrs="{ name: 'location' }"
                        :error-messages="form.errors.location"
                    />
                </template>
                <template v-else>
                    <FormPlainTextField
                        v-model="form.meeting_url"
                        :label="$t('teach.classes.fields.meeting_url')"
                        placeholder="https://"
                        :field-attrs="{ name: 'meeting_url', dir: 'ltr', inputmode: 'url' }"
                        :error-messages="form.errors.meeting_url"
                    />
                    <p class="teach-hint mt-1">{{ $t('teach.classes.fields.meeting_url_hint') }}</p>
                </template>
            </section>

            <section class="rq-card teach-section">
                <div class="class-form__row">
                    <FormPlainDatePicker
                        v-model="form.starts_on"
                        :label="$t('teach.classes.fields.starts_on')"
                        required
                        :disabled="readOnly"
                        :error-messages="form.errors.starts_on"
                    />
                    <FormPlainDatePicker
                        v-model="form.ends_on"
                        :label="$t('teach.classes.fields.ends_on')"
                        :min="form.starts_on || undefined"
                        :disabled="readOnly"
                        :error-messages="form.errors.ends_on"
                    />
                </div>

                <p class="form-plain-field__label font-weight-medium mb-2 mt-2">{{ $t('teach.classes.fields.days') }}</p>
                <div class="class-form__days mb-4">
                    <button
                        v-for="day in WEEK_ORDER"
                        :key="day"
                        type="button"
                        class="class-form__day"
                        :class="{ 'class-form__day--on': form.days.includes(day) }"
                        :aria-pressed="form.days.includes(day)"
                        @click="toggleDay(day)"
                    >
                        {{ $t(`days.${day}`) }}
                    </button>
                </div>

                <div class="class-form__row">
                    <FormPlainTimePicker
                        v-model="form.start_time"
                        :label="$t('teach.classes.fields.start_time')"
                        :disabled="readOnly"
                        :error-messages="form.errors.start_time"
                    />
                    <FormPlainTimePicker
                        v-model="form.end_time"
                        :label="$t('teach.classes.fields.end_time')"
                        :min="form.start_time || undefined"
                        :disabled="readOnly"
                        :error-messages="form.errors.end_time"
                    />
                </div>
            </section>

            <section class="rq-card teach-section">
                <div class="class-form__row">
                    <div>
                        <FormPlainTextField
                            v-model="form.price"
                            :label="`${$t('teach.classes.fields.price')} (${currency === 'LYD' ? 'د.ل' : currency})`"
                            :placeholder="$t('teach.classes.fields.price_hint')"
                            required
                            :field-attrs="{ name: 'price', inputmode: 'decimal', dir: 'ltr' }"
                            :error-messages="form.errors.price"
                        />
                    </div>
                    <div>
                        <FormPlainTextField
                            v-model="form.seats"
                            :label="$t('teach.classes.fields.seats')"
                            :placeholder="$t('teach.classes.fields.seats_hint')"
                            :field-attrs="{ name: 'seats', inputmode: 'numeric', dir: 'ltr' }"
                            :error-messages="form.errors.seats"
                        />
                    </div>
                </div>
            </section>

            <div v-if="!readOnly">
                <v-btn type="submit" class="rq-btn rq-btn--primary" size="large" :loading="form.processing">
                    {{ cls ? $t('teach.classes.save') : $t('teach.classes.create') }}
                </v-btn>
            </div>
        </fieldset>
    </form>

    <v-dialog v-model="cancelDialog" max-width="480">
        <v-card v-if="cls" class="app-form-dialog">
            <v-card-text class="pa-6">
                <h2 class="rq-dialog__title mb-2">{{ $t('teach.classes.cancel_title', { title: cls.title }) }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ $t('teach.classes.cancel_text') }}</p>
                <FormPlainTextarea
                    v-model="cancelForm.reason"
                    :label="$t('teach.classes.cancel_reason')"
                    :rows="2"
                    :maxlength="500"
                    :error-messages="cancelForm.errors.reason"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="cancelDialog = false">{{ $t('common.close') }}</v-btn>
                <v-btn class="rq-btn" variant="flat" color="error" :loading="cancelForm.processing" @click="cancelClass">
                    {{ $t('teach.classes.cancel_confirm') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import PageIntro from '@/Components/PageIntro.vue';
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ClassStatusBadge from '@/Components/ClassStatusBadge.vue';
import FormPlainDatePicker from '@/Components/formFields/FormPlainDatePicker.vue';
import FormPlainRichText from '@/Components/formFields/FormPlainRichText.vue';
import FormPlainTimePicker from '@/Components/formFields/FormPlainTimePicker.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';
import { WEEK_ORDER } from '@/utils/classFormat';

const props = defineProps({
    item: { type: Object, default: null },
    currency: { type: String, default: 'LYD' },
    publicUrl: { type: String, default: null },
});

const { t } = useI18n();
const cls = computed(() => props.item);
const readOnly = computed(() => cls.value?.status === 'canceled');

const form = useForm({
    title: cls.value?.title || '',
    description: cls.value?.description || '',
    type: cls.value?.type || 'in_person',
    location: cls.value?.location || '',
    map_url: cls.value?.map_url || '',
    image: null,
    remove_image: false,
    meeting_url: cls.value?.meeting_url || '',
    starts_on: cls.value?.starts_on || '',
    ends_on: cls.value?.ends_on || '',
    days: [...(cls.value?.days || [])],
    start_time: cls.value?.start_time || '',
    end_time: cls.value?.end_time || '',
    price: cls.value?.price ?? '',
    seats: cls.value?.seats ?? '',
});

const cancelDialog = ref(false);
const cancelForm = useForm({ reason: '' });

function toggleDay(day) {
    const index = form.days.indexOf(day);

    if (index === -1) {
        form.days.push(day);
    } else {
        form.days.splice(index, 1);
    }
}

// The promotional image: the saved one, or the file just chosen (shown before saving).
const imageInput = ref(null);
const chosenPreview = ref(null);
const imagePreview = computed(() => chosenPreview.value || (form.remove_image ? null : cls.value?.image_url || null));

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
    form.remove_image = Boolean(cls.value?.image_url);
    chosenPreview.value = null;
}

function save() {
    // A file needs a multipart body, and PHP only reads one on POST: update through _method.
    const send = (data) => {
        const { image, ...rest } = data;

        return { ...rest, ...(image ? { image } : {}) };
    };
    // The saved image comes back with the page, so the chosen file is let go.
    const saved = () => {
        form.image = null;
        form.remove_image = false;
        chosenPreview.value = null;
    };

    if (cls.value) {
        form.transform((data) => ({ ...send(data), _method: 'put' }))
            .post(route('teach.classes.update', cls.value.id), {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: saved,
            });
    } else {
        form.transform(send).post(route('teach.classes.store'), { forceFormData: true, onSuccess: saved });
    }
}

function act(action) {
    router.post(route(`teach.classes.${action}`, cls.value.id), {}, { preserveScroll: true });
}

function destroy() {
    if (window.confirm(t('teach.classes.delete_confirm'))) {
        router.delete(route('teach.classes.destroy', cls.value.id));
    }
}

function cancelClass() {
    cancelForm.post(route('teach.classes.cancel', cls.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelDialog.value = false;
        },
    });
}
</script>

<style scoped src="../teach.css"></style>

<style scoped>
.class-form {
    border: 0;
    margin: 0;
    padding: 0;
    min-width: 0;
}

.class-image {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}

.class-image__frame {
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

.class-image__frame--empty {
    border-style: dashed;
    color: var(--rq-text-subtle);
}

.class-image__frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.class-form__row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.class-form__days {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.class-form__day {
    padding: 8px 14px;
    border: 1px solid var(--rq-line-strong);
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface);
    color: var(--rq-text-body);
    font-family: var(--rq-font-body);
    font-size: 14px;
    cursor: pointer;
}

.class-form__day--on {
    border-color: var(--rq-action-primary);
    background: var(--rq-action-primary);
    color: var(--rq-text-on-primary);
}

.class-form__day:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}
</style>

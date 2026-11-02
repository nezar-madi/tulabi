<template>
    <Head :title="cls ? cls.title : $t('teach.classes.create_title')" />

    <PageIntro
        :title="cls ? cls.title : $t('teach.classes.create_title')"
        :description="cls ? '' : $t('teach.classes.form.lead')"
    >
        <template #title>
            {{ cls ? cls.title : $t('teach.classes.create_title') }}
            <ClassStatusBadge v-if="cls" :status="cls.status" :ended="cls.ended" class="ms-2 align-middle" />
        </template>

        <!-- Status actions -->
        <template v-if="cls" #actions>
            <div class="d-flex flex-wrap ga-2">
                <Link v-if="cls.status !== 'draft'" :href="route('teach.classes.students', cls.id)" class="text-decoration-none">
                    <v-btn class="text-none" variant="tonal" color="primary" prepend-icon="tabler:users">
                        {{ $t('teach.students.open') }}
                    </v-btn>
                </Link>
                <v-btn
                    v-if="publicUrl"
                    class="text-none"
                    variant="text"
                    color="primary"
                    prepend-icon="tabler:external-link"
                    :href="publicUrl"
                    target="_blank"
                    rel="noopener"
                >
                    {{ $t('teach.classes.view') }}
                </v-btn>
                <v-btn v-if="cls.status === 'draft'" class="text-none" variant="flat" color="primary" :disabled="!cls.image_url" @click="act('publish')">
                    {{ $t('teach.classes.publish') }}
                </v-btn>
                <v-btn v-if="cls.status === 'suspended'" class="text-none" variant="flat" color="primary" @click="openRestart">
                    {{ $t('teach.classes.restart') }}
                </v-btn>
                <v-btn v-if="cls.status === 'closed'" class="text-none" variant="flat" color="primary" @click="act('publish')">
                    {{ $t('teach.classes.reopen') }}
                </v-btn>
                <v-btn v-if="cls.status === 'published'" class="text-none" variant="outlined" color="primary" @click="act('close')">
                    {{ $t('teach.classes.close') }}
                </v-btn>
                <v-menu v-if="cls.status !== 'canceled'" location="bottom end">
                    <template #activator="{ props: menuProps }">
                        <v-btn v-bind="menuProps" icon variant="text" size="small" :aria-label="$t('teach.money.more')">
                            <v-icon icon="tabler:dots-vertical" />
                        </v-btn>
                    </template>
                    <v-list density="compact">
                        <v-list-item v-if="cls.status === 'draft'" :title="$t('teach.classes.delete')" base-color="error" @click="destroy" />
                        <v-list-item :title="$t('teach.classes.cancel')" base-color="error" @click="cancelDialog = true" />
                    </v-list>
                </v-menu>
            </div>
        </template>
    </PageIntro>

    <v-alert v-if="cls && cls.status === 'suspended'" type="warning" variant="tonal" class="mb-4" rounded="lg">
        {{ $t('teach.classes.suspended_note', { min: cls.min_seats }) }}
        <div class="mt-2">
            <v-btn class="text-none" variant="flat" color="primary" size="small" @click="openRestart">{{ $t('teach.classes.restart') }}</v-btn>
        </div>
    </v-alert>

    <v-alert v-if="readOnly" type="warning" variant="tonal" class="mb-4" rounded="lg">
        {{ $t('teach.classes.canceled_note') }}
        <div v-if="cls.cancel_reason" class="mt-1">{{ cls.cancel_reason }}</div>
    </v-alert>

    <div class="cf">
        <form id="class-form" class="cf__main" @submit.prevent="save">
            <fieldset :disabled="readOnly" class="cf__fieldset">
                <!-- 1. Basics: how the class is made, and its title -->
                <section id="cf-basics" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">1</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.form.basics') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.form.basics_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <!-- As in rqmana: a new class, or one made from a training program (its template) -->
                        <div class="cf-choices" role="radiogroup" :aria-label="$t('teach.classes.fields.how')">
                            <button
                                type="button"
                                role="radio"
                                class="cf-choice"
                                :class="{ 'cf-choice--on': mode === 'new' }"
                                :aria-checked="mode === 'new'"
                                @click="mode = 'new'"
                            >
                                <span class="rq-icon-tile cf-choice__tile"><v-icon icon="tabler:plus" /></span>
                                <span class="cf-choice__text">
                                    <strong>{{ $t('teach.classes.fields.mode_new') }}</strong>
                                    <small>{{ $t('teach.classes.form.mode_new_hint') }}</small>
                                </span>
                                <v-icon v-if="mode === 'new'" icon="tabler:circle-check" class="cf-choice__check" />
                            </button>
                            <button
                                type="button"
                                role="radio"
                                class="cf-choice"
                                :class="{ 'cf-choice--on': mode === 'program' }"
                                :aria-checked="mode === 'program'"
                                :disabled="programs.length === 0"
                                @click="mode = 'program'"
                            >
                                <span class="rq-icon-tile rq-icon-tile--violet cf-choice__tile"><v-icon icon="tabler:layout-grid" /></span>
                                <span class="cf-choice__text">
                                    <strong>{{ $t('teach.classes.fields.mode_program') }}</strong>
                                    <small v-if="programs.length">{{ $t('teach.classes.form.mode_program_hint') }}</small>
                                    <small v-else>{{ $t('teach.classes.fields.no_programs') }}</small>
                                </span>
                                <v-icon v-if="mode === 'program'" icon="tabler:circle-check" class="cf-choice__check" />
                            </button>
                        </div>
                        <p v-if="programs.length === 0" class="teach-hint mt-2">
                            <Link :href="route('teach.programs.create')">{{ $t('teach.programs.new') }}</Link>
                        </p>

                        <FormPlainSelect
                            v-if="mode === 'program'"
                            v-model="form.program_id"
                            :label="$t('teach.classes.fields.program')"
                            :items="programItems"
                            item-title="name"
                            item-value="id"
                            :placeholder="$t('teach.classes.fields.program_placeholder')"
                            required
                            :hint="$t('teach.classes.fields.program_hint')"
                            persistent-hint
                            :error-messages="form.errors.program_id"
                            class="mt-5"
                        />
                        <FormPlainTextField
                            v-model="form.title"
                            :label="$t('teach.classes.fields.title')"
                            :placeholder="$t('teach.classes.fields.title_hint')"
                            required
                            :field-attrs="{ name: 'title', maxlength: 150 }"
                            :error-messages="form.errors.title"
                            class="mt-5"
                        />
                    </div>
                </section>

                <!-- 2. Content: description, the three lists and the video -->
                <section id="cf-content" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">2</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.form.content') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.form.content_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <CourseContentFields :form="form" :description-label="$t('teach.classes.fields.description')" />
                    </div>
                </section>

                <!-- 3. Promotional image -->
                <section id="cf-image" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">3</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.fields.image') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.fields.image_required') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <input
                            ref="imageInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="d-none"
                            @change="pickImage"
                        >
                        <div v-if="imagePreview" class="cf-image">
                            <img :src="imagePreview" alt="">
                            <div class="cf-image__tools">
                                <v-btn class="text-none" variant="flat" color="surface" size="small" prepend-icon="tabler:upload" :disabled="readOnly" @click="imageInput.click()">
                                    {{ $t('teach.classes.fields.image_change') }}
                                </v-btn>
                                <v-btn class="text-none" variant="flat" color="surface" size="small" :disabled="readOnly" @click="clearImage">
                                    <v-icon icon="tabler:trash" color="error" />
                                </v-btn>
                            </div>
                        </div>
                        <button v-else type="button" class="cf-drop" :disabled="readOnly" @click="imageInput.click()">
                            <span class="rq-icon-tile cf-drop__tile"><v-icon icon="tabler:photo" /></span>
                            <strong>{{ $t('teach.classes.fields.image_upload') }}</strong>
                            <small>{{ $t('teach.classes.fields.image_hint') }}</small>
                        </button>
                        <small v-if="form.errors.image" class="text-error d-block mt-2">{{ form.errors.image }}</small>
                    </div>
                </section>

                <!-- 4. Where: in person (map link) or online (meeting link) -->
                <section id="cf-place" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">4</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.form.place') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.form.place_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <div class="cf-choices" role="radiogroup" :aria-label="$t('teach.classes.fields.type')">
                            <button
                                type="button"
                                role="radio"
                                class="cf-choice cf-choice--compact"
                                :class="{ 'cf-choice--on': form.type === 'in_person' }"
                                :aria-checked="form.type === 'in_person'"
                                @click="form.type = 'in_person'"
                            >
                                <span class="rq-icon-tile rq-icon-tile--teal cf-choice__tile"><v-icon icon="tabler:map-pin" /></span>
                                <span class="cf-choice__text"><strong>{{ $t('class.type.in_person') }}</strong></span>
                                <v-icon v-if="form.type === 'in_person'" icon="tabler:circle-check" class="cf-choice__check" />
                            </button>
                            <button
                                type="button"
                                role="radio"
                                class="cf-choice cf-choice--compact"
                                :class="{ 'cf-choice--on': form.type === 'online' }"
                                :aria-checked="form.type === 'online'"
                                @click="form.type = 'online'"
                            >
                                <span class="rq-icon-tile rq-icon-tile--sky cf-choice__tile"><v-icon icon="tabler:video" /></span>
                                <span class="cf-choice__text"><strong>{{ $t('class.type.online') }}</strong></span>
                                <v-icon v-if="form.type === 'online'" icon="tabler:circle-check" class="cf-choice__check" />
                            </button>
                        </div>

                        <div v-if="form.type === 'in_person'" class="mt-5">
                            <FormPlainTextField
                                v-model="form.map_url"
                                :label="$t('teach.classes.fields.map_url')"
                                placeholder="https://maps.app.goo.gl/..."
                                required
                                :field-attrs="{ name: 'map_url', dir: 'ltr', inputmode: 'url' }"
                                :error-messages="form.errors.map_url"
                            />
                            <p class="teach-hint mt-1">{{ $t('teach.classes.fields.map_url_hint') }}</p>
                            <FormPlainTextField
                                v-model="form.location"
                                :label="`${$t('teach.classes.fields.location')} (${$t('common.optional')})`"
                                :placeholder="$t('teach.classes.fields.location_hint')"
                                :field-attrs="{ name: 'location' }"
                                :error-messages="form.errors.location"
                                class="mt-4"
                            />
                        </div>
                        <div v-else class="mt-5">
                            <FormPlainTextField
                                v-model="form.meeting_url"
                                :label="$t('teach.classes.fields.meeting_url')"
                                placeholder="https://"
                                :field-attrs="{ name: 'meeting_url', dir: 'ltr', inputmode: 'url' }"
                                :error-messages="form.errors.meeting_url"
                            />
                            <p class="teach-hint mt-1">{{ $t('teach.classes.fields.meeting_url_hint') }}</p>
                        </div>
                    </div>
                </section>

                <!-- 5. When: period, days of study and time -->
                <section id="cf-schedule" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">5</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.form.schedule') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.form.schedule_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <div class="cf-row">
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

                        <!-- Days of study, as in rqmana: every day, chosen weekdays, or dates picked by hand -->
                        <FormPlainSelect
                            v-model="form.schedule_type"
                            :label="$t('teach.classes.fields.schedule_type')"
                            :items="scheduleItems"
                            item-title="title"
                            item-value="value"
                            required
                            :disabled="readOnly"
                            :error-messages="form.errors.schedule_type"
                            class="mt-5"
                        />

                        <div v-if="form.schedule_type === 'weekly'" class="mt-4">
                            <p class="form-plain-field__label font-weight-medium mb-2">{{ $t('teach.classes.fields.days') }}</p>
                            <div class="cf-days">
                                <button
                                    v-for="day in WEEK_ORDER"
                                    :key="day"
                                    type="button"
                                    class="cf-day"
                                    :class="{ 'cf-day--on': form.days.includes(day) }"
                                    :aria-pressed="form.days.includes(day)"
                                    @click="toggleDay(day)"
                                >
                                    {{ $t(`days.${day}`) }}
                                </button>
                            </div>
                            <small v-if="form.errors.days || form.errors['days.0']" class="text-error d-block mt-2">{{ form.errors.days || form.errors['days.0'] }}</small>
                        </div>

                        <FormPlainDatesPicker
                            v-else-if="form.schedule_type === 'manual'"
                            v-model="form.manual_dates"
                            :label="$t('teach.classes.fields.manual_dates')"
                            :hint="$t('teach.classes.fields.manual_dates_hint')"
                            persistent-hint
                            required
                            :min="form.starts_on || undefined"
                            :max="form.ends_on || undefined"
                            :disabled="readOnly"
                            :error-messages="form.errors.manual_dates"
                            class="mt-4"
                        />

                        <div class="cf-row mt-5">
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
                    </div>
                </section>

                <!-- 6. Price and seats, and «تعليق البدء تلقائيًا» -->
                <section id="cf-seats" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num">6</span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.form.price') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.form.price_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <div class="cf-row">
                            <FormPlainTextField
                                v-model="form.price"
                                :label="`${$t('teach.classes.fields.price')} (${currencyLabel})`"
                                :placeholder="$t('teach.classes.fields.price_hint')"
                                required
                                :field-attrs="{ name: 'price', inputmode: 'decimal', dir: 'ltr' }"
                                :error-messages="form.errors.price"
                            />
                            <FormPlainTextField
                                v-model="form.seats"
                                :label="`${$t('teach.classes.fields.seats')} (${$t('common.optional')})`"
                                :placeholder="$t('teach.classes.fields.seats_hint')"
                                :field-attrs="{ name: 'seats', inputmode: 'numeric', dir: 'ltr' }"
                                :error-messages="form.errors.seats"
                            />
                        </div>

                        <div class="cf-option mt-5" :class="{ 'cf-option--on': form.auto_suspend }">
                            <div class="cf-option__head">
                                <span class="rq-icon-tile rq-icon-tile--amber cf-option__tile"><v-icon icon="tabler:hourglass" /></span>
                                <div class="cf-option__text">
                                    <strong>{{ $t('teach.classes.fields.auto_suspend') }}</strong>
                                    <small>{{ $t('teach.classes.fields.auto_suspend_hint') }}</small>
                                </div>
                                <v-switch
                                    v-model="form.auto_suspend"
                                    color="primary"
                                    density="compact"
                                    hide-details
                                    inset
                                    class="cf-option__switch"
                                    :aria-label="$t('teach.classes.fields.auto_suspend')"
                                    :disabled="readOnly"
                                />
                            </div>
                            <FormPlainTextField
                                v-if="form.auto_suspend"
                                v-model="form.min_seats"
                                :label="$t('teach.classes.fields.min_seats')"
                                :placeholder="$t('teach.classes.fields.min_seats_hint')"
                                required
                                :field-attrs="{ name: 'min_seats', inputmode: 'numeric', dir: 'ltr' }"
                                :error-messages="form.errors.min_seats"
                                class="cf-option__field"
                            />
                        </div>
                    </div>
                </section>
            </fieldset>
        </form>

        <!-- Side panel: a live preview, what is still missing, and the save button -->
        <aside class="cf__side">
            <div class="cf-side">
                <div class="cf-preview">
                    <div class="cf-preview__media">
                        <img v-if="imagePreview" :src="imagePreview" alt="">
                        <v-icon v-else icon="tabler:photo" size="32" />
                    </div>
                    <div class="cf-preview__body">
                        <span class="cf-preview__kicker">{{ $t(`class.type.${form.type}`) }}</span>
                        <strong class="cf-preview__title">{{ form.title || $t('teach.classes.form.untitled') }}</strong>
                        <span class="cf-preview__meta">
                            <v-icon icon="tabler:calendar-event" size="15" />
                            {{ form.starts_on ? formatDay(form.starts_on) : $t('teach.classes.form.no_date') }}
                        </span>
                        <span class="cf-preview__price rq-number">{{ previewPrice }}</span>
                    </div>
                </div>

                <v-btn
                    v-if="!readOnly"
                    type="submit"
                    form="class-form"
                    class="text-none cf-side__save"
                    variant="flat"
                    color="primary"
                    size="large"
                    block
                    :loading="form.processing"
                >
                    {{ cls ? $t('teach.classes.save') : $t('teach.classes.create') }}
                </v-btn>
                <p v-if="!cls && !readOnly" class="cf-side__hint">{{ $t('teach.classes.form.draft_note') }}</p>
                <div class="cf-ready">
                    <div class="cf-ready__head">
                        <strong>{{ $t('teach.classes.form.ready_title') }}</strong>
                        <span class="rq-number">{{ readyDone }}/{{ readiness.length }}</span>
                    </div>
                    <span class="cf-ready__bar" aria-hidden="true"><span :style="{ width: `${Math.round((readyDone / readiness.length) * 100)}%` }" /></span>
                    <ul class="cf-ready__list">
                        <li v-for="item in readiness" :key="item.key" :class="{ 'cf-ready__item--done': item.done }">
                            <button type="button" @click="scrollTo(item.section)">
                                <v-icon :icon="item.done ? 'tabler:circle-check' : 'tabler:circle'" size="18" />
                                {{ $t(`teach.classes.form.ready.${item.key}`) }}
                            </button>
                        </li>
                    </ul>
                    <p class="cf-ready__note">{{ $t('teach.classes.form.ready_note') }}</p>
                </div>

            </div>
        </aside>
    </div>

    <!-- Phones: the save button stays in reach at the bottom -->
    <div v-if="!readOnly" class="cf-bar">
        <span class="cf-bar__status">{{ $t('teach.classes.form.ready_short', { done: readyDone, total: readiness.length }) }}</span>
        <v-btn
            type="submit"
            form="class-form"
            class="text-none"
            variant="flat"
            color="primary"
            :loading="form.processing"
        >
            {{ cls ? $t('teach.classes.save') : $t('teach.classes.create') }}
        </v-btn>
    </div>

    <v-dialog v-model="restartOpen" max-width="480" scrollable>
        <v-card v-if="cls" class="app-form-dialog">
            <v-card-text class="app-form-dialog__body pt-6 pb-2">
                <h2 class="rq-dialog__title mb-2">{{ $t('teach.classes.restart_title', { title: cls.title }) }}</h2>
                <p class="rq-dialog__subtitle mb-4">{{ $t('teach.classes.restart_text') }}</p>
                <FormPlainDatePicker
                    v-model="restartForm.starts_on"
                    :label="$t('teach.classes.fields.new_starts_on')"
                    required
                    :min="today"
                    :error-messages="restartForm.errors.starts_on"
                />
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="restartOpen = false">{{ $t('common.close') }}</v-btn>
                <v-btn class="rq-btn rq-btn--primary" :loading="restartForm.processing" :disabled="!restartForm.starts_on" @click="restart">
                    {{ $t('teach.classes.restart_confirm') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

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
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ClassStatusBadge from '@/Components/ClassStatusBadge.vue';
import CourseContentFields from '@/Components/CourseContentFields.vue';
import FormPlainDatePicker from '@/Components/formFields/FormPlainDatePicker.vue';
import FormPlainDatesPicker from '@/Components/formFields/FormPlainDatesPicker.vue';
import FormPlainSelect from '@/Components/formFields/FormPlainSelect.vue';
import FormPlainTimePicker from '@/Components/formFields/FormPlainTimePicker.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';
import { WEEK_ORDER, formatDay, formatPrice } from '@/utils/classFormat';

const props = defineProps({
    item: { type: Object, default: null },
    currency: { type: String, default: 'LYD' },
    // The programs (course templates) a course may be put in: [{ id, name, description, image_url }].
    programs: { type: Array, default: () => [] },
    publicUrl: { type: String, default: null },
    // A program chosen on the way in (a new class from this program).
    selectedProgram: { type: Number, default: null },
    // Libya's today, the earliest date a suspended class may restart on.
    today: { type: String, default: '' },
});

const { t } = useI18n();
const cls = computed(() => props.item);
const readOnly = computed(() => cls.value?.status === 'canceled');

const programItems = computed(() => props.programs);

const form = useForm({
    program_id: cls.value?.program_id ?? props.selectedProgram ?? null,
    title: cls.value?.title || '',
    description: cls.value?.description || '',
    learn: [...(cls.value?.learn || [])],
    requirements: [...(cls.value?.requirements || [])],
    audience: [...(cls.value?.audience || [])],
    promo_video_url: cls.value?.promo_video_url || '',
    auto_suspend: cls.value?.auto_suspend || false,
    min_seats: cls.value?.min_seats ?? '',
    type: cls.value?.type || 'in_person',
    location: cls.value?.location || '',
    map_url: cls.value?.map_url || '',
    image: null,
    remove_image: false,
    no_template_image: false,
    meeting_url: cls.value?.meeting_url || '',
    starts_on: cls.value?.starts_on || '',
    ends_on: cls.value?.ends_on || '',
    schedule_type: cls.value?.schedule_type || 'weekly',
    days: [...(cls.value?.days || [])],
    manual_dates: [...(cls.value?.manual_dates || [])],
    start_time: cls.value?.start_time || '',
    end_time: cls.value?.end_time || '',
    price: cls.value?.price ?? '',
    seats: cls.value?.seats ?? '',
});

// How the class is made: new, or from a program (its template). Going back to «جديد» lets go of the program.
const mode = ref(form.program_id ? 'program' : 'new');

watch(mode, (value) => {
    if (value === 'new') {
        form.program_id = null;
    }
});

// A program is the template of a class: on a new class, choosing one fills in the title, the
// description, the three lists, the video and the image, and the instructor edits them from
// there. A field the instructor already changed is not overwritten.
const template = computed(() => props.programs.find((program) => program.id === form.program_id) || null);
const sameList = (a, b) => a.length === b.length && a.every((item, index) => item === b[index]);

function fillFromProgram(previous, next) {
    if (!form.title || form.title === previous?.name) {
        form.title = next?.name || '';
    }

    if (!form.description.trim() || form.description === previous?.description) {
        form.description = next?.description || '';
    }

    ['learn', 'requirements', 'audience'].forEach((key) => {
        if (form[key].length === 0 || sameList(form[key], previous?.[key] || [])) {
            form[key] = [...(next?.[key] || [])];
        }
    });

    if (!form.promo_video_url || form.promo_video_url === previous?.promo_video_url) {
        form.promo_video_url = next?.promo_video_url || '';
    }

    form.no_template_image = false;
}

watch(() => form.program_id, (_, previousId) => {
    if (cls.value) {
        return;
    }

    fillFromProgram(props.programs.find((program) => program.id === previousId) || null, template.value);
});

// Arriving with a program already chosen (a new class from the program's page).
if (!cls.value && form.program_id) {
    fillFromProgram(null, template.value);
}

// A suspended class starts again on a new date
const restartOpen = ref(false);
const restartForm = useForm({ starts_on: '' });

function openRestart() {
    restartForm.reset();
    restartForm.clearErrors();
    restartForm.starts_on = props.today;
    restartOpen.value = true;
}

function restart() {
    restartForm.post(route('teach.classes.reactivate', cls.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            restartOpen.value = false;
        },
    });
}

const cancelDialog = ref(false);
const cancelForm = useForm({ reason: '' });

const scheduleItems = computed(() => ['daily', 'weekly', 'manual'].map((value) => ({
    value,
    title: t(`teach.classes.fields.schedule.${value}`),
})));

// Switching the type drops what belongs to the other types.
watch(() => form.schedule_type, (type) => {
    if (type !== 'weekly') {
        form.days = [];
    }

    if (type !== 'manual') {
        form.manual_dates = [];
    }
});

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
const imagePreview = computed(() => chosenPreview.value
    || (form.remove_image ? null : cls.value?.image_url || null)
    || (!cls.value && !form.no_template_image ? template.value?.image_url || null : null));

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
    // Letting go of the image a program brought: the server then does not copy it.
    form.no_template_image = !cls.value && Boolean(template.value?.image_url);
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

// ---------- Side panel: a live preview and what is still missing to publish ----------
const currencyLabel = computed(() => (props.currency === 'LYD' ? 'د.ل' : props.currency));

const previewPrice = computed(() => (form.price === '' || form.price === null
    ? t('teach.classes.form.no_price')
    : formatPrice({ price: String(form.price), currency: props.currency }, t)));

const readiness = computed(() => [
    { key: 'title', section: 'cf-basics', done: form.title.trim().length >= 3 },
    { key: 'description', section: 'cf-content', done: form.description.trim() !== '' },
    { key: 'learn', section: 'cf-content', done: form.learn.some((item) => item.trim() !== '') },
    { key: 'image', section: 'cf-image', done: Boolean(imagePreview.value) },
    {
        key: form.type === 'in_person' ? 'map' : 'meeting',
        section: 'cf-place',
        done: form.type === 'in_person' ? form.map_url.trim() !== '' : true,
    },
    { key: 'starts_on', section: 'cf-schedule', done: Boolean(form.starts_on) },
    {
        key: 'days',
        section: 'cf-schedule',
        done: form.schedule_type === 'daily'
            || (form.schedule_type === 'weekly' && form.days.length > 0)
            || (form.schedule_type === 'manual' && form.manual_dates.length > 0),
    },
    { key: 'price', section: 'cf-seats', done: form.price !== '' && form.price !== null },
]);
const readyDone = computed(() => readiness.value.filter((item) => item.done).length);

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
<style scoped src="../courseForm.css"></style>

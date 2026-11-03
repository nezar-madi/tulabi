<template>
    <Head :title="cls ? cls.title : $t('teach.classes.create_title')" />

    <PageIntro
        class="cf-intro"
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
                    <v-btn class="text-none cf-intro__students" variant="text" prepend-icon="tabler:users">
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
                <v-btn v-if="cls.status === 'published'" class="text-none" variant="text" color="error" prepend-icon="tabler:lock" @click="act('close')">
                    {{ $t('teach.classes.close') }}
                </v-btn>
                <v-menu v-if="cls.status !== 'canceled'" location="bottom end">
                    <template #activator="{ props: menuProps }">
                        <v-btn v-bind="menuProps" icon variant="text" size="small" :aria-label="$t('teach.money.more')">
                            <v-icon icon="tabler:dots-vertical" />
                        </v-btn>
                    </template>
                    <v-list density="compact">
                        <!-- A draft is deleted; only a class that was published is canceled. -->
                        <v-list-item v-if="cls.status === 'draft'" class="cf-menu-item" base-color="error" @click="deleteDialog = true">
                            <v-list-item-title>{{ $t('teach.classes.delete') }}</v-list-item-title>
                            <template #append><v-icon icon="tabler:trash" size="16" /></template>
                        </v-list-item>
                        <v-list-item v-else class="cf-menu-item" base-color="error" @click="cancelDialog = true">
                            <v-list-item-title>{{ $t('teach.classes.cancel') }}</v-list-item-title>
                            <template #append><v-icon icon="tabler:ban" size="16" /></template>
                        </v-list-item>
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

    <!-- After a save that moved the dates, hours or place of a class with students -->
    <v-alert v-if="scheduleShareUrl" type="success" variant="tonal" class="mb-4" rounded="lg" icon="tabler:calendar-event">
        {{ $t('teach.classes.schedule_changed') }}
        <div class="mt-2">
            <v-btn class="text-none" variant="flat" color="success" size="small" :href="scheduleShareUrl" target="_blank" rel="noopener">
                <v-icon icon="tabler:brand-whatsapp" start />
                {{ $t('teach.classes.meeting.share_schedule') }}
            </v-btn>
        </div>
    </v-alert>

    <v-alert v-if="readOnly" type="warning" variant="tonal" class="mb-4" rounded="lg">
        {{ $t('teach.classes.canceled_note') }}
        <div v-if="cls.cancel_reason" class="mt-1">{{ cls.cancel_reason }}</div>
    </v-alert>

    <div class="cf">
        <form id="class-form" class="cf__main" @submit.prevent="submit">
            <!-- The steps: one at a time on a new class; tabs to open in any order on an existing one -->
            <nav class="cf-steps" :class="{ 'cf-steps--tabs': cls }" :aria-label="$t('teach.classes.wizard.label')">
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
                        <strong>{{ $t(`teach.classes.wizard.steps.${entry.key}`) }}</strong>
                    </span>
                </button>
            </nav>

            <fieldset :disabled="readOnly" class="cf__fieldset">
                <!-- 1. Basics: how the class is made, and its title -->
                <section v-show="step === 0" id="cf-basics" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:pencil" size="16" /></span>
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
                <section v-show="step === 1" id="cf-content" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:file-text" size="16" /></span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.form.content') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.form.content_hint') }}</p>
                        </div>
                    </header>
                    <div class="cf-section__body">
                        <CourseContentFields :form="form" :description-label="$t('teach.classes.fields.description')" lists-required />
                    </div>
                </section>

                <!-- 3. Promotional image -->
                <section v-show="step === 1" id="cf-image" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:photo" size="16" /></span>
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
                <section v-show="step === 2" id="cf-place" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:map-pin" size="16" /></span>
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

                            <!-- The saved link, sent from the instructor's own WhatsApp (to the class group or a student). -->
                            <div v-if="meetingShareUrl" class="cf-share" :class="{ 'cf-share--new': meetingChanged }">
                                <p v-if="meetingChanged" class="cf-share__text">{{ $t('teach.classes.meeting.changed') }}</p>
                                <v-btn
                                    class="rq-btn"
                                    :variant="meetingChanged ? 'flat' : 'tonal'"
                                    color="success"
                                    size="small"
                                    :href="meetingShareUrl"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <v-icon icon="tabler:brand-whatsapp" start />
                                    {{ $t('teach.classes.meeting.share') }}
                                </v-btn>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 5. When: period, days of study and time -->
                <section v-show="step === 2" id="cf-schedule" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:calendar-event" size="16" /></span>
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
                                :min="cls ? undefined : today"
                                :disabled="readOnly"
                                :error-messages="form.errors.starts_on"
                            />
                            <FormPlainDatePicker
                                v-model="form.ends_on"
                                :label="$t('teach.classes.fields.ends_on')"
                                required
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
                                required
                                :disabled="readOnly"
                                :error-messages="form.errors.start_time"
                            />
                            <FormPlainTimePicker
                                v-model="form.end_time"
                                :label="$t('teach.classes.fields.end_time')"
                                required
                                :min="form.start_time || undefined"
                                :disabled="readOnly"
                                :error-messages="form.errors.end_time"
                            />
                        </div>
                    </div>
                </section>

                <!-- 6. Price and seats, and «تعليق البدء تلقائيًا» -->
                <section v-show="step === 3" id="cf-seats" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:cash" size="16" /></span>
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
                                :disabled="priceLocked"
                                :hint="priceLocked ? $t('teach.classes.fields.price_locked') : ''"
                                :persistent-hint="priceLocked"
                                :field-attrs="{ name: 'price', inputmode: 'decimal', dir: 'ltr' }"
                                :error-messages="form.errors.price"
                            />
                            <FormPlainTextField
                                v-model="form.seats"
                                :label="$t('teach.classes.fields.seats')"
                                :placeholder="$t('teach.classes.fields.seats_hint')"
                                required
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

                <!-- Before creating: everything on one card, each line opens its step -->
                <section v-if="!cls" v-show="step === 3" id="cf-review" class="cf-section">
                    <header class="cf-section__head">
                        <span class="cf-section__num"><v-icon icon="tabler:circle-check" size="16" /></span>
                        <div>
                            <h2 class="cf-section__title">{{ $t('teach.classes.wizard.review') }}</h2>
                            <p class="cf-section__hint">{{ $t('teach.classes.wizard.review_hint') }}</p>
                        </div>
                    </header>
                    <dl class="cf-review">
                        <div v-for="row in summary" :key="row.key" class="cf-review__row">
                            <dt>{{ $t(`teach.classes.wizard.summary.${row.key}`) }}</dt>
                            <dd :class="{ 'cf-review__empty': row.empty }">{{ row.value }}</dd>
                            <button type="button" class="cf-review__edit" @click="goTo(row.step)">{{ $t('teach.classes.edit') }}</button>
                        </div>
                    </dl>
                </section>
            </fieldset>

            <div v-if="!readOnly" class="cf-nav">
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
                    {{ $t('teach.classes.wizard.next', { name: $t(`teach.classes.wizard.steps.${steps[step + 1].key}`) }) }}
                </v-btn>
                <v-btn
                    v-else-if="!cls"
                    type="submit"
                    class="text-none"
                    variant="flat"
                    color="primary"
                    prepend-icon="tabler:check"
                    :loading="form.processing"
                >
                    {{ $t('teach.classes.create') }}
                </v-btn>
            </div>
        </form>

        <!-- Side panel: a live preview, what is still missing, and the save button -->
        <aside class="cf__side">
            <div class="cf-side">
                <div class="cf-preview">
                    <div class="cf-preview__media">
                        <img v-if="imagePreview" :src="imagePreview" alt="">
                        <v-icon v-else icon="tabler:photo" size="32" />
                    </div>
                </div>

                <v-btn
                    v-if="!readOnly && cls"
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
                <div class="cf-ready">
                    <div class="cf-ready__head">
                        <strong>{{ $t('teach.classes.form.ready_title') }}</strong>
                        <span class="rq-number">{{ readyDone }}/{{ readiness.length }}</span>
                    </div>
                    <span class="cf-ready__bar" aria-hidden="true"><span :style="{ width: `${Math.round((readyDone / readiness.length) * 100)}%` }" /></span>
                    <ul class="cf-ready__list">
                        <li v-for="item in readiness" :key="item.key" :class="{ 'cf-ready__item--done': item.done }">
                            <button type="button" @click="openSection(item.section)">
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
        <v-btn
            v-if="!cls && step > 0"
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
            {{ cls
                ? $t('teach.classes.form.ready_short', { done: readyDone, total: readiness.length })
                : $t('teach.classes.wizard.bar', { n: step + 1, total: steps.length, name: $t(`teach.classes.wizard.steps.${steps[step].key}`) }) }}
        </span>
        <v-btn
            v-if="!cls && step < last"
            class="text-none"
            variant="flat"
            color="primary"
            append-icon="tabler:arrow-left"
            @click="next"
        >
            {{ $t('teach.classes.wizard.next_short') }}
        </v-btn>
        <v-btn
            v-else
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

    <ClassCreatedDialog v-if="cls" v-model="createdOpen" :title="cls.title" />

    <v-dialog v-model="deleteDialog" max-width="420">
        <v-card v-if="cls" class="app-form-dialog">
            <v-card-text class="pa-6">
                <div class="cf-confirm__icon"><v-icon icon="tabler:trash" size="26" /></div>
                <h2 class="rq-dialog__title mb-2">{{ $t('teach.classes.delete_title') }}</h2>
                <p class="rq-dialog__subtitle mb-0">{{ $t('teach.classes.delete_text', { title: cls.title }) }}</p>
            </v-card-text>
            <v-card-actions class="pa-4">
                <v-spacer />
                <v-btn class="rq-btn" variant="text" @click="deleteDialog = false">{{ $t('common.close') }}</v-btn>
                <v-btn class="rq-btn" variant="flat" color="error" :loading="deleting" @click="destroy">
                    {{ $t('teach.classes.delete_confirm_button') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import ClassCreatedDialog from '@/Components/ClassCreatedDialog.vue';
import PageIntro from '@/Components/PageIntro.vue';
import { computed, nextTick, ref, watch } from 'vue';
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
import { WEEK_ORDER, formatDays, formatPeriod, formatPrice, formatSchedule } from '@/utils/classFormat';

const props = defineProps({
    item: { type: Object, default: null },
    currency: { type: String, default: 'LYD' },
    // The programs (course templates) a course may be put in: [{ id, name, description, image_url }].
    programs: { type: Array, default: () => [] },
    publicUrl: { type: String, default: null },
    // wa.me message with the saved meeting link (online class), and whether this save changed it.
    meetingShareUrl: { type: String, default: null },
    meetingChanged: { type: Boolean, default: false },
    // wa.me message with the new dates, hours and place, after a save that changed them (class with students).
    scheduleShareUrl: { type: String, default: null },
    // The class was saved a moment ago: show the «تم إنشاء الفصل» dialog.
    justCreated: { type: Boolean, default: false },
    // A program chosen on the way in (a new class from this program).
    selectedProgram: { type: Number, default: null },
    // Libya's today, the earliest date a suspended class may restart on.
    today: { type: String, default: '' },
});

const { t } = useI18n();
const cls = computed(() => props.item);
const readOnly = computed(() => cls.value?.status === 'canceled');
// The price is fixed once a student has joined (the server refuses a change too).
const priceLocked = computed(() => Boolean(cls.value?.price_locked));

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
    // 0 = free, and 0 seats = no limit.
    price: cls.value?.price ?? '0',
    seats: cls.value?.seats ?? '0',
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
const createdOpen = ref(props.justCreated);
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

        return { ...rest, from_program: mode.value === 'program', ...(image ? { image } : {}) };
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
                onError: openFirstError,
            });
    } else {
        // Inertia's post keeps the page's state by default. Keep it only when the form comes back with
        // errors; the new draft (same component) must start fresh: edit mode and the «تم إنشاء الفصل» dialog.
        form.transform(send).post(route('teach.classes.store'), {
            forceFormData: true,
            preserveState: (page) => Object.keys(page.props.errors || {}).length > 0,
            onSuccess: saved,
            onError: openFirstError,
        });
    }
}

// ---------- Steps ----------
// A new class is filled one step at a time («التالي» checks the step first); an existing one
// opens any step as a tab. Each field belongs to one step, so a server error opens its step.
const steps = [
    { key: 'class', sections: ['cf-basics'], fields: ['from_program', 'program_id', 'title'] },
    { key: 'content', sections: ['cf-content', 'cf-image'], fields: ['description', 'learn', 'requirements', 'audience', 'promo_video_url', 'image', 'remove_image'] },
    {
        key: 'when',
        sections: ['cf-place', 'cf-schedule'],
        fields: ['type', 'location', 'map_url', 'meeting_url', 'starts_on', 'ends_on', 'schedule_type', 'days', 'manual_dates', 'start_time', 'end_time'],
    },
    { key: 'price', sections: ['cf-seats', 'cf-review'], fields: ['price', 'seats', 'auto_suspend', 'min_seats'] },
];
const last = steps.length - 1;
const step = ref(0);
const reached = ref(cls.value ? last : 0);

const stepOf = (field) => steps.findIndex((entry) => entry.fields.includes(field.split('.')[0]));
const stepHasErrors = (index) => Object.keys(form.errors).some((field) => stepOf(field) === index);
const canOpen = (index) => index <= reached.value;

// What a step still lacks, as { field: message } (the server checks everything again).
function missing(index) {
    const need = {};
    const required = t('teach.classes.wizard.required');

    if (index === 0) {
        if (mode.value === 'program' && !form.program_id) {
            need.program_id = required;
        }

        if (form.title.trim().length < 3) {
            need.title = t('teach.classes.wizard.title_short');
        }
    } else if (index === 1) {
        if (!form.description.trim()) {
            need.description = required;
        }

        ['learn', 'requirements', 'audience'].forEach((list) => {
            if (!form[list].some((item) => item.trim() !== '')) {
                need[list] = t('teach.classes.wizard.pick_item');
            }
        });
    } else if (index === 2) {
        if (form.type === 'in_person' && !form.map_url.trim()) {
            need.map_url = required;
        }

        ['starts_on', 'ends_on', 'start_time', 'end_time'].forEach((field) => {
            if (!form[field]) {
                need[field] = required;
            }
        });

        // No past dates (a date the class already had may stay as it is).
        if (form.starts_on && form.starts_on < props.today && form.starts_on !== cls.value?.starts_on) {
            need.starts_on = t('teach.classes.wizard.start_past');
        }

        if (form.ends_on && form.ends_on < props.today && form.ends_on !== cls.value?.ends_on) {
            need.ends_on = t('teach.classes.wizard.end_past');
        }

        if (form.schedule_type === 'weekly' && form.days.length === 0) {
            need.days = t('teach.classes.wizard.pick_days');
        }

        // A weekly class starts on one of its days (its first session).
        const startDay = form.starts_on ? new Date(`${form.starts_on}T12:00:00Z`).getUTCDay() : null;
        const daysChanged = !cls.value || form.starts_on !== cls.value.starts_on || cls.value.schedule_type !== 'weekly'
            || [...form.days].sort().join() !== [...(cls.value.days || [])].sort().join();

        if (form.schedule_type === 'weekly' && form.days.length && startDay !== null && !need.starts_on
            && daysChanged && !form.days.includes(startDay)) {
            need.starts_on = t('teach.classes.wizard.start_day', { days: formatDays(form.days, t) });
        }

        if (form.schedule_type === 'manual' && form.manual_dates.length === 0) {
            need.manual_dates = t('teach.classes.wizard.pick_dates');
        }
    } else if (index === 3) {
        ['price', 'seats'].forEach((field) => {
            if (String(form[field] ?? '').trim() === '') {
                need[field] = required;
            }
        });

        if (form.auto_suspend && (form.min_seats === '' || form.min_seats === null)) {
            need.min_seats = required;
        }
    }

    return need;
}

const stepDone = (index) => index !== step.value && index <= reached.value && !stepHasErrors(index)
    && Object.keys(missing(index)).length === 0;

function scrollToForm() {
    nextTick(() => document.getElementById('class-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function goTo(index, force = false) {
    if (index < 0 || index > last || (!force && !canOpen(index))) {
        return;
    }

    step.value = index;
    reached.value = Math.max(reached.value, index);
    scrollToForm();
}

// «التالي»: the step's own checks first, then the next step.
function next() {
    const need = missing(step.value);

    form.clearErrors(...steps[step.value].fields);

    if (Object.keys(need).length > 0) {
        form.setError(need);
        nextTick(() => document.querySelector('#class-form .v-messages__message, #class-form small.text-error')
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' }));

        return;
    }

    goTo(step.value + 1, true);
}

function submit() {
    if (!cls.value && step.value < last) {
        next();

        return;
    }

    // Saving runs the same checks as «التالي» on every step (an edit opens any step as a tab,
    // so it is checked here as a whole), then opens the first step that still lacks something.
    const need = steps.reduce((all, _, index) => ({ ...all, ...missing(index) }), {});

    if (Object.keys(need).length > 0) {
        form.clearErrors();
        form.setError(need);
        openFirstError();
        nextTick(() => nextTick(() => document.querySelector('#class-form .v-messages__message, #class-form small.text-error')
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' })));

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

const summary = computed(() => {
    const learnCount = form.learn.filter((entry) => entry.trim() !== '').length;
    const rows = [
        { key: 'title', step: 0, value: form.title.trim() },
        ...(template.value ? [{ key: 'program', step: 0, value: template.value.name }] : []),
        { key: 'content', step: 1, value: form.description.trim() ? t(learnCount ? 'teach.classes.wizard.content_value' : 'teach.classes.wizard.content_plain', { count: learnCount }) : '' },
        { key: 'image', step: 1, value: imagePreview.value ? t('teach.classes.wizard.image_yes') : t('teach.classes.wizard.image_no'), empty: !imagePreview.value },
        { key: 'place', step: 2, value: form.type === 'in_person' ? (form.location.trim() || t('class.type.in_person')) : t('class.type.online') },
        { key: 'period', step: 2, value: form.starts_on ? formatPeriod(form, t) : '' },
        { key: 'schedule', step: 2, value: formatSchedule(form, t) },
        { key: 'price', step: 3, value: form.price === '' || form.price === null ? '' : previewPrice.value },
        { key: 'seats', step: 3, value: Number(form.seats) > 0 ? String(form.seats) : t('teach.classes.wizard.no_limit') },
    ];

    return rows.map((row) => ({ ...row, empty: row.empty || row.value === '', value: row.value || '—' }));
});

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

const deleteDialog = ref(false);
const deleting = ref(false);

function destroy() {
    router.delete(route('teach.classes.destroy', cls.value.id), {
        onStart: () => { deleting.value = true; },
        onFinish: () => { deleting.value = false; },
    });
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

<style scoped>
/* ---------- Steps ---------- */
.cf__main {
    scroll-margin-top: 88px;
}

.cf-steps {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: var(--rq-space-5);
    padding: 8px;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-card);
    background: var(--rq-surface);
}

.cf-step {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 8px 10px;
    border: 0;
    border-radius: var(--rq-radius-md);
    background: none;
    color: var(--rq-text-muted);
    font: inherit;
    text-align: start;
    cursor: pointer;
    transition: background-color var(--rq-dur-fast) var(--rq-ease);
}

.cf-step:hover:not(:disabled) {
    background: var(--rq-surface-hover);
}

.cf-step:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}

.cf-step:disabled {
    cursor: default;
    opacity: 0.55;
}

.cf-step__num {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 30px;
    height: 30px;
    border: 1.5px solid var(--rq-line-strong);
    border-radius: 50%;
    background: var(--rq-surface);
    font-family: var(--rq-font-display);
    font-size: 14px;
    font-weight: 700;
    transition: all var(--rq-dur-fast) var(--rq-ease);
}

.cf-step__text {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.35;
}

.cf-step__text small {
    font-size: 11px;
    color: var(--rq-text-subtle);
}

.cf-step__text strong {
    font-family: var(--rq-font-display);
    font-size: 14px;
    font-weight: 600;
}

.cf-step--on {
    background: var(--rq-surface-hover);
    color: var(--rq-text-heading);
}

.cf-step--on .cf-step__num {
    border-color: var(--rq-action-primary);
    background: var(--rq-action-primary);
    color: var(--rq-text-on-primary);
    box-shadow: 0 0 0 4px var(--rq-icon-tile);
}

.cf-step--done .cf-step__num {
    border-color: var(--rq-success);
    background: var(--rq-success);
    color: #fff;
}

.cf-step--done {
    color: var(--rq-text-body);
}

.cf-step--error .cf-step__num {
    border-color: var(--rq-danger);
    background: var(--rq-danger-tint);
    color: var(--rq-danger);
}

/* A thin bar under the current step */
.cf-step--on::after {
    position: absolute;
    inset-inline: 10px;
    bottom: -9px;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: var(--rq-action-primary);
    content: "";
}

/* Phones: numbers only, the current step keeps its name */
@media (max-width: 759.98px) {
    .cf-steps {
        grid-template-columns: repeat(4, auto);
        justify-content: space-between;
    }

    .cf-step:not(.cf-step--on) .cf-step__text {
        display: none;
    }
}

/* ---------- Back / next ---------- */
.cf-nav {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: var(--rq-space-5);
}

@media (max-width: 1099.98px) {
    .cf-nav {
        display: none; /* the bottom bar does this on narrow screens */
    }
}

/* ---------- Review ---------- */
.cf-review {
    margin: 0;
    padding: 6px 24px 12px;
}

.cf-review__row {
    display: grid;
    grid-template-columns: 130px minmax(0, 1fr) auto;
    align-items: baseline;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--rq-line-soft);
}

.cf-review__row:last-child {
    border-bottom: 0;
}

.cf-review dt {
    font-size: 13px;
    color: var(--rq-text-muted);
}

.cf-review dd {
    margin: 0;
    font-size: 14px;
    color: var(--rq-text-heading);
    overflow-wrap: anywhere;
}

.cf-review__empty {
    color: var(--rq-text-subtle) !important;
}

.cf-review__edit {
    padding: 0;
    border: 0;
    background: none;
    color: var(--rq-text-link);
    font: inherit;
    font-size: 13px;
    cursor: pointer;
}

.cf-review__edit:hover {
    text-decoration: underline;
}

@media (max-width: 599.98px) {
    .cf-review {
        padding-inline: 16px;
    }

    .cf-review__row {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .cf-review dd {
        grid-column: 1;
    }

    .cf-review__edit {
        grid-row: 1;
        grid-column: 2;
    }
}

.cf-share {
    margin-top: 12px;
}

/* The title sits on the same line as the buttons beside it (both 40px high, centered). */
.cf-intro :deep(.page-intro__title) {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    min-height: 40px;
}

.cf-intro__students {
    color: var(--rq-accent-violet) !important;
}

.cf-confirm__icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin-bottom: 14px;
    border-radius: 50%;
    background: var(--rq-danger-tint, rgb(220 38 38 / 0.1));
    color: var(--rq-danger, #dc2626);
}

/* «حذف المسودة» / «إلغاء الفصل» in the ⋮ menu: small, as secondary actions. */
.cf-menu-item {
    min-height: 34px !important;
    padding-inline: 12px 16px !important;
}

.cf-menu-item :deep(.v-list-item__append) {
    margin-inline-start: 10px;
}

.cf-menu-item :deep(.v-list-item__append > .v-icon) {
    margin-inline-start: 0;
}

.cf-menu-item :deep(.v-list-item-title) {
    font-size: 13px;
    font-weight: 500;
}

.cf-share--new {
    padding: 12px 14px;
    border: 1px solid var(--rq-success);
    border-radius: var(--rq-radius-md, 12px);
    background: var(--rq-success-tint);
}

.cf-share__text {
    margin: 0 0 10px;
    font-size: 13px;
    line-height: 1.7;
    color: var(--rq-text-heading);
}
</style>

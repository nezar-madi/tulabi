<?php

namespace App\Http\Requests\Teach;

use App\Models\Program;
use App\Models\TeachingClass;
use App\Rules\EmbeddableVideoUrl;
use App\Rules\GoogleMapsUrl;
use App\Support\CourseContent;
use App\Support\ScheduleClash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create or edit a class. A canceled class can no longer be edited.
 */
class SaveClassRequest extends InstructorRequest
{
    public function authorize(): bool
    {
        $class = $this->route('class');

        return parent::authorize() && ! ($class instanceof TeachingClass && $class->isCanceled());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'days' => array_values(array_unique(array_map('intval', (array) $this->input('days', [])))),
            'manual_dates' => array_values(array_unique(array_filter(array_map(
                fn ($date) => is_string($date) ? trim($date) : '',
                (array) $this->input('manual_dates', []),
            )))),
            'from_program' => $this->boolean('from_program'),
            'description' => CourseContent::cleanDescription($this->input('description')),
            'learn' => CourseContent::cleanList($this->input('learn', [])),
            'requirements' => CourseContent::cleanList($this->input('requirements', [])),
            'audience' => CourseContent::cleanList($this->input('audience', [])),
            'promo_video_url' => $this->filled('promo_video_url') ? trim((string) $this->input('promo_video_url')) : null,
            'auto_suspend' => $this->boolean('auto_suspend'),
            'min_seats' => $this->boolean('auto_suspend') && $this->filled('min_seats') ? $this->input('min_seats') : null,
            'remove_image' => $this->boolean('remove_image'),
            'no_template_image' => $this->boolean('no_template_image'),
            'ends_on' => $this->filled('ends_on') ? $this->input('ends_on') : null,
            'start_time' => $this->filled('start_time') ? $this->input('start_time') : null,
            'end_time' => $this->filled('end_time') ? $this->input('end_time') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $inPerson = $this->input('type') === TeachingClass::TYPE_IN_PERSON;

        return [
            ...CourseContent::rules(),
            // A class needs at least one item in each list (a program may leave them empty).
            ...collect(CourseContent::LISTS)->mapWithKeys(fn (string $list) => [
                $list => ['required', 'array', 'min:1', 'max:'.CourseContent::ITEMS_MAX],
            ])->all(),
            // «من برنامج تدريبي» needs the program; «فصل جديد» may have none.
            'from_program' => ['boolean'],
            'program_id' => [
                Rule::requiredIf($this->boolean('from_program')),
                'nullable', 'integer',
                Rule::exists('programs', 'id')->where('instructor_id', $this->instructor()?->getKey()),
            ],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'type' => ['required', Rule::in([TeachingClass::TYPE_IN_PERSON, TeachingClass::TYPE_ONLINE])],
            // The written place is optional; the Google Maps link is what an in-person class needs.
            'location' => ['nullable', 'string', 'max:255'],
            'map_url' => [$inPerson ? 'required' : 'nullable', 'string', 'max:500', new GoogleMapsUrl],
            'meeting_url' => ['nullable', 'string', 'max:500', 'url:https,http'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before:2100-01-01'],
            // As in rqmana: both dates and both times are required, the end not before the start.
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'before:2100-01-01'],
            // Daily needs no days; weekly needs at least one weekday; manual needs at least one date.
            'schedule_type' => ['required', Rule::in([
                TeachingClass::SCHEDULE_DAILY, TeachingClass::SCHEDULE_WEEKLY, TeachingClass::SCHEDULE_MANUAL,
            ])],
            'days' => ['array', 'max:7', Rule::requiredIf($this->input('schedule_type') === TeachingClass::SCHEDULE_WEEKLY)],
            'days.*' => ['integer', 'between:0,6'],
            'manual_dates' => ['array', 'max:200', Rule::requiredIf($this->input('schedule_type') === TeachingClass::SCHEDULE_MANUAL)],
            'manual_dates.*' => ['date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before:2100-01-01'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            // 0 = free.
            'price' => ['required', 'numeric', 'min:0', 'max:1000000', 'decimal:0,3'],
            // 0 = no limit (saved as null).
            'seats' => ['required', 'integer', 'min:0', 'max:10000'],
            // A promotional video, embedded on the class page (YouTube, Vimeo or Dailymotion).
            'promo_video_url' => ['nullable', 'string', 'max:500', new EmbeddableVideoUrl],
            // «تعليق البدء تلقائيًا»: needs a minimum, which cannot be more than the seats.
            'auto_suspend' => ['boolean'],
            'min_seats' => [
                Rule::requiredIf($this->boolean('auto_suspend')),
                'nullable', 'integer', 'min:1', 'max:10000',
                (int) $this->input('seats') > 0 ? 'lte:seats' : 'nullable',
            ],
            'image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
                'dimensions:min_width=600,min_height=300,max_width=6000,max_height=6000',
            ],
            'remove_image' => ['boolean'],
            'no_template_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return collect(CourseContent::LISTS)->flatMap(fn (string $list) => [
            "{$list}.required" => __('classes.list_required'),
            "{$list}.min" => __('classes.list_required'),
        ])->all();
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            // A class already shown to visitors (or waiting to restart) keeps its image: take the
            // image away only by closing it to the public first, never by editing.
            $class = $this->route('class');

            if ($class instanceof TeachingClass && $class->image_path !== null && $this->boolean('remove_image')
                && $this->newImage() === null
                && in_array($class->status, [
                    TeachingClass::STATUS_PUBLISHED, TeachingClass::STATUS_CLOSED, TeachingClass::STATUS_SUSPENDED,
                ], true)) {
                $validator->errors()->add('image', __('classes.image_required_published'));
            }

            // A stopped program takes no new courses (one already in it may stay, as in rqmana).
            if ($this->filled('program_id') && ! $validator->errors()->has('program_id')) {
                $program = Program::query()->find((int) $this->input('program_id'));
                $class = $this->route('class');
                $alreadyIn = $class instanceof TeachingClass && (int) $class->program_id === (int) $this->input('program_id');

                if ($program !== null && ! $program->is_active && ! $alreadyIn) {
                    $validator->errors()->add('program_id', __('programs.inactive'));
                }
            }

            $this->checkSchedule($validator);

            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if (is_string($start) && is_string($end) && $end <= $start) {
                $validator->errors()->add('end_time', __('classes.end_time_after'));
            }

            $this->checkClash($validator);
        }];
    }

    /**
     * Hand-picked dates must fall inside the course's period, and the chosen weekdays must
     * each occur in it (a short course that never meets on a chosen day is a mistake).
     */
    private function checkSchedule(Validator $validator): void
    {
        $startsOn = (string) $this->input('starts_on');
        $endsOn = $this->filled('ends_on') ? (string) $this->input('ends_on') : null;

        if ($validator->errors()->hasAny(['starts_on', 'ends_on'])) {
            return;
        }

        $type = $this->input('schedule_type');

        if ($type === TeachingClass::SCHEDULE_MANUAL && ! $validator->errors()->hasAny(['manual_dates', 'manual_dates.*'])) {
            foreach ((array) $this->input('manual_dates', []) as $date) {
                if ($date < $startsOn || ($endsOn !== null && $date > $endsOn)) {
                    $validator->errors()->add('manual_dates', __('classes.manual_dates_outside_range'));

                    return;
                }
            }
        }

        if ($type === TeachingClass::SCHEDULE_WEEKLY && $endsOn !== null && ! $validator->errors()->has('days')) {
            $start = Carbon::parse($startsOn);
            $end = Carbon::parse($endsOn);

            // A period of a week or more always has every weekday in it.
            if ($start->diffInDays($end) < 6) {
                $present = [];

                for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                    $present[] = $day->dayOfWeek;
                }

                if (array_diff((array) $this->input('days', []), $present) !== []) {
                    $validator->errors()->add('days', __('classes.days_outside_range'));
                }
            }
        }
    }

    /** The sessions may not overlap another active class of the instructor (App\Support\ScheduleClash). */
    private function checkClash(Validator $validator): void
    {
        $fields = ['starts_on', 'ends_on', 'schedule_type', 'days', 'days.*', 'manual_dates', 'manual_dates.*', 'start_time', 'end_time'];
        $instructor = $this->instructor();

        if ($instructor === null || $validator->errors()->hasAny($fields)) {
            return;
        }

        $class = $this->route('class');
        $clash = ScheduleClash::find($instructor, [
            'starts_on' => (string) $this->input('starts_on'),
            'ends_on' => $this->input('ends_on'),
            'schedule_type' => (string) $this->input('schedule_type'),
            'days' => (array) $this->input('days', []),
            'manual_dates' => (array) $this->input('manual_dates', []),
            'start_time' => $this->input('start_time'),
            'end_time' => $this->input('end_time'),
        ], $class instanceof TeachingClass ? $class->getKey() : null);

        if ($clash !== null) {
            $validator->errors()->add('start_time', ScheduleClash::message($clash));
        }
    }

    public function newImage(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * The validated data ready to save: description filtered, fields of the other type emptied.
     *
     * @return array<string, mixed>
     */
    public function classData(): array
    {
        $data = $this->validated();
        $online = $data['type'] === TeachingClass::TYPE_ONLINE;

        $type = $data['schedule_type'];
        $days = $type === TeachingClass::SCHEDULE_WEEKLY ? ($data['days'] ?? []) : [];
        sort($days);
        $dates = $type === TeachingClass::SCHEDULE_MANUAL ? ($data['manual_dates'] ?? []) : [];
        sort($dates);

        unset($data['image'], $data['remove_image'], $data['no_template_image'], $data['from_program']);
        $lists = CourseContent::listsFrom($data);
        $location = trim((string) ($data['location'] ?? ''));

        return [
            ...$data,
            'program_id' => ($data['program_id'] ?? null) ?: null,
            'title' => trim($data['title']),
            'description' => CourseContent::cleanDescription($data['description']),
            ...$lists,
            'auto_suspend' => (bool) ($data['auto_suspend'] ?? false),
            'min_seats' => ($data['auto_suspend'] ?? false) ? ($data['min_seats'] ?? null) : null,
            'location' => $online || $location === '' ? null : $location,
            'map_url' => $online ? null : trim((string) $data['map_url']),
            'meeting_url' => $online ? (($data['meeting_url'] ?? null) ?: null) : null,
            'days' => $days === [] ? null : $days,
            'manual_dates' => $dates === [] ? null : $dates,
            'seats' => (int) $data['seats'] > 0 ? (int) $data['seats'] : null,
        ];
    }
}

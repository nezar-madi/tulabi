<?php

namespace App\Http\Requests\Teach;

use App\Models\Program;
use App\Models\TeachingClass;
use App\Rules\GoogleMapsUrl;
use App\Support\SafeHtml;
use Illuminate\Http\UploadedFile;
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
            'seats' => $this->filled('seats') ? $this->input('seats') : null,
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
            // Optional: the course belongs to one of the instructor's own programs.
            'program_id' => [
                'nullable', 'integer',
                Rule::exists('programs', 'id')->where('instructor_id', $this->instructor()?->getKey()),
            ],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'type' => ['required', Rule::in([TeachingClass::TYPE_IN_PERSON, TeachingClass::TYPE_ONLINE])],
            // The written place is optional; the Google Maps link is what an in-person class needs.
            'location' => ['nullable', 'string', 'max:255'],
            'map_url' => [$inPerson ? 'required' : 'nullable', 'string', 'max:500', new GoogleMapsUrl],
            'meeting_url' => ['nullable', 'string', 'max:500', 'url:https,http'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before:2100-01-01'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'before:2100-01-01'],
            'days' => ['array', 'max:7'],
            'days.*' => ['integer', 'between:0,6'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000', 'decimal:0,3'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
                'dimensions:min_width=600,min_height=300,max_width=6000,max_height=6000',
            ],
            'remove_image' => ['boolean'],
            'no_template_image' => ['boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            // The description is cleaned on save: a box with only empty tags does not count.
            if (! $validator->errors()->has('description')
                && ! SafeHtml::hasText(SafeHtml::description((string) $this->input('description')))) {
                $validator->errors()->add('description', __('validation.required', ['attribute' => __('validation.attributes.description')]));
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

            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if (is_string($start) && is_string($end) && $end <= $start) {
                $validator->errors()->add('end_time', __('classes.end_time_after'));
            }
        }];
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

        $description = SafeHtml::description($data['description'] ?? null);
        $days = $data['days'] ?? [];
        sort($days);

        unset($data['image'], $data['remove_image'], $data['no_template_image']);
        $location = trim((string) ($data['location'] ?? ''));

        return [
            ...$data,
            'program_id' => ($data['program_id'] ?? null) ?: null,
            'title' => trim($data['title']),
            'description' => $description,
            'location' => $online || $location === '' ? null : $location,
            'map_url' => $online ? null : trim((string) $data['map_url']),
            'meeting_url' => $online ? (($data['meeting_url'] ?? null) ?: null) : null,
            'days' => $days === [] ? null : $days,
        ];
    }
}

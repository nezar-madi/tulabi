<?php

namespace App\Http\Requests\Teach;

use App\Models\TeachingClass;
use App\Support\SafeHtml;
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
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:20000'],
            'type' => ['required', Rule::in([TeachingClass::TYPE_IN_PERSON, TeachingClass::TYPE_ONLINE])],
            'location' => [$inPerson ? 'required' : 'nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'string', 'max:500', 'url:https,http'],
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before:2100-01-01'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on', 'before:2100-01-01'],
            'days' => ['array', 'max:7'],
            'days.*' => ['integer', 'between:0,6'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000', 'decimal:0,3'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $start = $this->input('start_time');
            $end = $this->input('end_time');

            if (is_string($start) && is_string($end) && $end <= $start) {
                $validator->errors()->add('end_time', __('classes.end_time_after'));
            }
        }];
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

        return [
            ...$data,
            'title' => trim($data['title']),
            'description' => SafeHtml::hasText($description) ? $description : null,
            'location' => $online ? null : trim((string) ($data['location'] ?? '')),
            'meeting_url' => $online ? (($data['meeting_url'] ?? null) ?: null) : null,
            'days' => $days === [] ? null : $days,
        ];
    }
}

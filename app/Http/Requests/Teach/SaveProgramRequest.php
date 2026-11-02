<?php

namespace App\Http\Requests\Teach;

use App\Models\Program;
use App\Support\SafeHtml;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create or edit a program. Only its own instructor may edit it.
 */
class SaveProgramRequest extends InstructorRequest
{
    public function authorize(): bool
    {
        $program = $this->route('program');

        return parent::authorize()
            && (! $program instanceof Program || $program->instructor_id === $this->instructor()?->getKey());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? preg_replace('/\s+/u', ' ', trim($this->input('name'))) : $this->input('name'),
            'is_active' => $this->boolean('is_active'),
            'is_published' => $this->boolean('is_published'),
            'is_featured' => $this->boolean('is_featured'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $program = $this->route('program');

        return [
            'name' => [
                'required', 'string', 'min:3', 'max:'.Program::NAME_MAX,
                Rule::unique('programs', 'name')
                    ->where('instructor_id', $this->instructor()?->getKey())
                    ->ignore($program instanceof Program ? $program->getKey() : null),
            ],
            'description' => ['required', 'string', 'max:20000'],
            'is_active' => ['boolean'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'image' => [
                'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
                'dimensions:min_width=600,min_height=300,max_width=6000,max_height=6000',
            ],
            'remove_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => __('programs.name_taken')];
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
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function programData(): array
    {
        $data = $this->validated();
        unset($data['image'], $data['remove_image']);

        return [
            ...$data,
            'name' => trim($data['name']),
            'description' => SafeHtml::description($data['description']),
        ];
    }

    public function newImage(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }
}

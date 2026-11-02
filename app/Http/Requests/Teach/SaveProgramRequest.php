<?php

namespace App\Http\Requests\Teach;

use App\Models\Program;
use App\Rules\EmbeddableVideoUrl;
use App\Support\CourseContent;
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
            'description' => CourseContent::cleanDescription($this->input('description')),
            'learn' => CourseContent::cleanList($this->input('learn', [])),
            'requirements' => CourseContent::cleanList($this->input('requirements', [])),
            'audience' => CourseContent::cleanList($this->input('audience', [])),
            'promo_video_url' => $this->filled('promo_video_url') ? trim((string) $this->input('promo_video_url')) : null,
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
            ...CourseContent::rules(),
            'name' => [
                'required', 'string', 'min:3', 'max:'.Program::NAME_MAX,
                Rule::unique('programs', 'name')
                    ->where('instructor_id', $this->instructor()?->getKey())
                    ->ignore($program instanceof Program ? $program->getKey() : null),
            ],
            'promo_video_url' => ['nullable', 'string', 'max:500', new EmbeddableVideoUrl],
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
            // A program shown on the instructor's page needs its promotional image: the saved one
            // (not being removed) or a new one.
            $program = $this->route('program');
            $hasImage = $this->newImage() !== null
                || ($program instanceof Program && $program->image_path !== null && ! $this->boolean('remove_image'));

            if ($this->boolean('is_published') && ! $hasImage && ! $validator->errors()->has('image')) {
                $validator->errors()->add('image', __('programs.image_required_to_publish'));
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
            'description' => CourseContent::cleanDescription($data['description']),
            ...CourseContent::listsFrom($data),
        ];
    }

    public function newImage(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }
}

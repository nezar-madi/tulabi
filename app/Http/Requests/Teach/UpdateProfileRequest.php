<?php

namespace App\Http\Requests\Teach;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\LibyanMobile;
use App\Support\AboutSections;
use App\Support\InstructorSlug;
use App\Support\ProfileLinks;
use App\Support\SafeHtml;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * «صفحتي»: what visitors see on tulabi.ly/{slug}.
 */
class UpdateProfileRequest extends InstructorRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput('whatsapp');

        $this->merge([
            'slug' => InstructorSlug::normalize($this->input('slug')) ?: null,
            'links' => array_values(array_filter(
                array_map(fn ($link) => trim((string) $link), (array) $this->input('links', [])),
                fn (string $link) => $link !== '',
            )),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'min:3', 'max:120'],
            'specialty' => ['required', 'string', 'min:2', 'max:120'],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            // The «نبذة» sections: the first («من أنا») may be empty, the others need a body.
            'sections' => ['required', 'array', 'min:1', 'max:'.AboutSections::MAX],
            'sections.*.title' => ['required', 'string', 'min:2', 'max:'.AboutSections::TITLE_MAX],
            'sections.*.body' => ['nullable', 'string', 'max:'.AboutSections::BODY_MAX],
            'whatsapp' => ['nullable', 'string', new LibyanMobile],
            'links' => ['array', 'max:'.ProfileLinks::MAX],
            'links.*' => ['string', 'max:300', 'url:http,https'],
            'slug' => [
                'nullable', 'string',
                'min:'.InstructorSlug::MIN_LENGTH, 'max:'.InstructorSlug::MAX_LENGTH,
                'regex:'.InstructorSlug::PATTERN,
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_string($value) && InstructorSlug::isReserved($value)) {
                        $fail(__('instructors.slug_reserved'));
                    }
                },
                Rule::unique('instructors', 'slug')->ignore($this->instructor()?->getKey()),
            ],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach (array_values((array) $this->input('sections', [])) as $index => $section) {
                $body = (string) ($section['body'] ?? '');

                if ($index > 0 && ! SafeHtml::hasText(SafeHtml::description($body))
                    && ! $validator->errors()->has("sections.{$index}.body")) {
                    $validator->errors()->add("sections.{$index}.body", __('instructors.section_body_required'));
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('instructors.slug_format'),
            'slug.unique' => __('instructors.slug_taken'),
            'links.*.url' => __('instructors.link_invalid'),
        ];
    }
}

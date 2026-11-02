<?php

namespace App\Http\Requests\Teach;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\LibyanMobile;
use App\Support\InstructorSlug;
use App\Support\ProfileLinks;
use Closure;
use Illuminate\Validation\Rule;

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
            'bio' => ['nullable', 'string', 'max:2000'],
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

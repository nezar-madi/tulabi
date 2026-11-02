<?php

namespace App\Http\Requests\Teach;

class UploadPhotoRequest extends InstructorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
                'dimensions:min_width=200,min_height=200,max_width=5000,max_height=5000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['photo.dimensions' => __('instructors.photo_dimensions')];
    }
}

<?php

namespace App\Http\Requests\Admin;

class UpdateSettingsRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('applications_notify_email'))) {
            $this->merge(['applications_notify_email' => strtolower(trim($this->input('applications_notify_email')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'applications_notify_email' => ['nullable', 'string', 'email:filter', 'max:190'],
        ];
    }
}

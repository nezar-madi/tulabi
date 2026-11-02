<?php

namespace App\Http\Requests\Teach;

use App\Support\TulabiClock;

/**
 * A suspended class starts again on a new date: today or later (Libya's calendar).
 */
class ReactivateClassRequest extends InstructorRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.TulabiClock::today(), 'before:2100-01-01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['starts_on.after_or_equal' => __('classes.restart_date_past')];
    }
}

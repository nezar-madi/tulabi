<?php

namespace App\Rules;

use App\Support\VideoEmbed;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A promotional video link the site can embed (see App\Support\VideoEmbed).
 */
class EmbeddableVideoUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || VideoEmbed::embedUrl($value) === null) {
            $fail(__('classes.video_invalid'));
        }
    }
}

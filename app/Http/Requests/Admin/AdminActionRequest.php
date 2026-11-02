<?php

namespace App\Http\Requests\Admin;

/**
 * An admin action without input (accept, suspend, issue a link).
 */
class AdminActionRequest extends AdminRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}

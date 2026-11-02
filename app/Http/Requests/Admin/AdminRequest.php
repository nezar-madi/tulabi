<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin actions: only a signed-in admin with the second factor set up.
 */
abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user('admin');

        return $admin instanceof Admin && $admin->hasTwoFactorEnabled();
    }
}

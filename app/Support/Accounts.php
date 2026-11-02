<?php

namespace App\Support;

use App\Models\User;

/**
 * Making accounts. Everyone signs up the same way (name, phone, password, optional email);
 * the instructor side is added later when the admin accepts an application.
 */
class Accounts
{
    /**
     * @param  array{name: string, phone: string, password: string, email?: string|null}  $data  phone already normalised
     */
    public function register(array $data): User
    {
        return User::query()->forceCreate([
            'name' => trim($data['name']),
            'phone' => $data['phone'],
            'email' => self::normalizeEmail($data['email'] ?? null),
            'password' => $data['password'],
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email : The admin\'s email} {--name= : Display name}';

    protected $description = 'Create an admin account (the authenticator is set up at first login)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) ($this->option('name') ?: $this->ask('Name', 'الإدارة')));

        if (Admin::query()->where('email', $email)->exists()) {
            $this->error("An admin with the email {$email} already exists.");

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least 12 characters)');
        $confirm = (string) $this->secret('Password again');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            ['email' => ['required', 'email:filter'], 'name' => ['required', 'max:120'], 'password' => ['required', 'min:12']],
        );

        if ($validator->fails() || $password !== $confirm) {
            $this->error($password !== $confirm ? 'The passwords do not match.' : $validator->errors()->first());

            return self::FAILURE;
        }

        $admin = new Admin(['name' => $name, 'email' => $email]);
        $admin->forceFill(['password' => $password])->save();

        $this->info("Admin {$email} created. Sign in at ".route('admin.login').' and set up the authenticator.');

        return self::SUCCESS;
    }
}

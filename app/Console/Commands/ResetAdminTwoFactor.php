<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Support\TwoFactor\AdminTwoFactor;
use Illuminate\Console\Command;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:two-factor-reset {email : The admin\'s email}';

    protected $description = 'Clear an admin\'s authenticator (for a lost phone); it must be set up again at next login';

    public function handle(AdminTwoFactor $twoFactor): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $admin = Admin::query()->where('email', $email)->first();

        if (! $admin instanceof Admin) {
            $this->error("No admin with the email {$email}.");

            return self::FAILURE;
        }

        $twoFactor->disable($admin);

        $this->warn("Two-factor authentication was cleared for {$email}.");
        $this->warn('Anyone with this account\'s password can now sign in and set up a new authenticator.');

        return self::SUCCESS;
    }
}

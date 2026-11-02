<?php

namespace App\Console\Commands;

use App\Support\ClassLifecycle;
use Illuminate\Console\Command;

/**
 * «تعليق البدء تلقائيًا»: suspend every class that reached its start date with fewer accepted
 * students than its minimum. Scheduled hourly (routes/console.php); the instructor's own pages
 * also run it for their classes, so nothing waits for the schedule.
 */
class CheckClassStarts extends Command
{
    protected $signature = 'classes:check-start';

    protected $description = 'Suspend classes that reached their start date below their minimum number of students';

    public function handle(ClassLifecycle $lifecycle): int
    {
        $count = $lifecycle->suspendUnderfilled();

        $this->info($count.' class(es) suspended.');

        return self::SUCCESS;
    }
}

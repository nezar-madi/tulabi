<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // One sqlite database in memory, migrated for each test (phpunit.xml).
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pages render app.blade.php, whose @vite needs a build manifest the tests don't have.
        $this->withoutVite();
    }
}

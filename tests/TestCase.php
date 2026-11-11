<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    public function createApplication()
    {
        $app = parent::createApplication();
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            throw new RuntimeException("FinTrack tests require the 'pgsql' driver. Configured driver: $driver.");
        }
        $result = DB::selectOne('select current_database() as database_name, current_user as role_name');
        if ($result->database_name === 'fintrack_test' && $result->role_name === 'fintrack_test') {
            return $app;
        } else {
            throw new RuntimeException(
                "FinTrack tests require database 'fintrack_test' and PostgreSQL role 'fintrack_test'. Connected to database $result->database_name as role $result->role_name."
            );
        }
    }
}

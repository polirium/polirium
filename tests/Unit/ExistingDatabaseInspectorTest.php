<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Installer\Support\ExistingDatabaseInspector;
use PHPUnit\Framework\TestCase;

class ExistingDatabaseInspectorTest extends TestCase
{
    public function test_it_recognizes_an_empty_database(): void
    {
        $inspection = (new ExistingDatabaseInspector())->classify([]);

        $this->assertSame('empty', $inspection['status']);
        $this->assertSame(0, $inspection['table_count']);
    }

    public function test_it_recognizes_a_legacy_polirium_database(): void
    {
        $inspection = (new ExistingDatabaseInspector())->classify(
            ['migrations', 'users', 'products', 'customers'],
            ['2019_01_01_000000_create_users_table'],
        );

        $this->assertSame('polirium', $inspection['status']);
        $this->assertFalse($inspection['has_modules_table']);
    }

    public function test_it_does_not_treat_an_arbitrary_laravel_database_as_polirium(): void
    {
        $inspection = (new ExistingDatabaseInspector())->classify(
            ['migrations', 'users', 'jobs', 'cache'],
            ['0001_01_01_000000_create_users_table'],
        );

        $this->assertSame('unknown', $inspection['status']);
    }
}

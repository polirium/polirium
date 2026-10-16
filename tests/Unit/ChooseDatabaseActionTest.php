<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Installer\Steps\ChooseDatabaseAction;
use App\Installer\Support\ExistingDatabaseInspector;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ChooseDatabaseActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'legacy_polirium',
        ]);
    }

    public function test_keep_mode_is_allowed_only_for_a_recognized_polirium_database(): void
    {
        $step = new ChooseDatabaseAction($this->inspector('polirium', true));

        $step->process(['install_mode' => ChooseDatabaseAction::MODE_UPGRADE]);

        $this->assertSame(ChooseDatabaseAction::MODE_UPGRADE, session(ChooseDatabaseAction::SESSION_MODE));
        $this->assertSame('legacy_polirium', session(ChooseDatabaseAction::SESSION_DATABASE));
        $this->assertTrue(session(ChooseDatabaseAction::SESSION_HAD_MODULES));
    }

    public function test_unknown_database_cannot_use_keep_mode(): void
    {
        $step = new ChooseDatabaseAction($this->inspector('unknown'));

        $this->expectException(RuntimeException::class);
        $step->process(['install_mode' => ChooseDatabaseAction::MODE_UPGRADE]);
    }

    public function test_reinstall_requires_the_exact_database_name(): void
    {
        $step = new ChooseDatabaseAction($this->inspector('polirium'));

        $this->expectException(RuntimeException::class);
        $step->process([
            'install_mode' => ChooseDatabaseAction::MODE_FRESH,
            'reinstall_confirmation' => 'wrong_database',
        ]);
    }

    public function test_empty_database_uses_fresh_mode_without_a_destructive_confirmation(): void
    {
        $step = new ChooseDatabaseAction($this->inspector('empty'));

        $step->process([]);

        $this->assertSame(ChooseDatabaseAction::MODE_FRESH, session(ChooseDatabaseAction::SESSION_MODE));
    }

    private function inspector(string $status, bool $hasModules = false): ExistingDatabaseInspector
    {
        $inspector = Mockery::mock(ExistingDatabaseInspector::class);
        $inspector->shouldReceive('inspect')->atLeast()->once()->andReturn([
            'status' => $status,
            'table_count' => $status === 'empty' ? 0 : 12,
            'has_modules_table' => $hasModules,
        ]);

        return $inspector;
    }
}

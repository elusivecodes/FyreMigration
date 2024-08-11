<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\DateTime\DateTime;
use Fyre\DB\TypeParser;
use Fyre\Forge\ForgeRegistry;
use Fyre\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;

final class MigrationHistoryTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testAllAfterMigration(): void
    {
        MigrationRunner::migrate();

        $history = MigrationRunner::getHistory()->all();

        $this->assertSame(
            [
                1,
                2,
                3,
            ],
            array_column($history, 'version')
        );
    }

    public function testAllAfterRollback(): void
    {
        MigrationRunner::migrate();
        MigrationRunner::rollback();

        $history = MigrationRunner::getHistory()->all();

        $this->assertSame(
            [
                1,
                2,
                3,
                2,
                1,
                null,
            ],
            array_column($history, 'version')
        );
    }

    public function testCurrent(): void
    {
        $this->assertNull(
            MigrationRunner::getHistory()->current()
        );
    }

    public function testCurrentAfterMigration(): void
    {
        MigrationRunner::migrate();

        $this->assertSame(
            3,
            MigrationRunner::getHistory()->current()
        );
    }

    public function testCurrentAfterRollback(): void
    {
        MigrationRunner::migrate();
        MigrationRunner::rollback();

        $this->assertNull(
            MigrationRunner::getHistory()->current()
        );
    }

    public function testSchema(): void
    {
        MigrationRunner::getHistory();

        $this->assertSame(
            [],
            ForgeRegistry::getForge($this->db)
                ->build('migrations')
                ->clear()
                ->addColumn('id', [
                    'type' => 'int',
                    'autoIncrement' => true,
                ])
                ->addColumn('version', [
                    'type' => 'int',
                    'nullable' => true,
                ])
                ->addColumn('timestamp', [
                    'type' => 'timestamp',
                    'default' => 'current_timestamp()',
                ])
                ->setPrimaryKey('id')
                ->sql()
        );
    }

    public function testTimestamp(): void
    {
        $now = DateTime::now();

        MigrationRunner::migrate();

        $history = MigrationRunner::getHistory()->all();

        $this->assertGreaterThanOrEqual(
            $now,
            TypeParser::use('datetime')->parse($history[0]['timestamp'])
        );
    }
}

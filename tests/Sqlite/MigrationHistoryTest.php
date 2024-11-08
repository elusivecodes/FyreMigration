<?php
declare(strict_types=1);

namespace Tests\Sqlite;

use Fyre\DateTime\DateTime;
use Fyre\DB\Types\DateTimeType;
use Fyre\DB\Types\IntegerType;
use PHPUnit\Framework\TestCase;

final class MigrationHistoryTest extends TestCase
{
    use SqliteConnectionTrait;

    public function testAllAfterMigration(): void
    {
        $this->migrationRunner->migrate();

        $history = $this->migrationRunner->getHistory()->all();

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
        $this->migrationRunner->migrate();
        $this->migrationRunner->rollback();

        $history = $this->migrationRunner->getHistory()->all();

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
            $this->migrationRunner->getHistory()->current()
        );
    }

    public function testCurrentAfterMigration(): void
    {
        $this->migrationRunner->migrate();

        $this->assertSame(
            3,
            $this->migrationRunner->getHistory()->current()
        );
    }

    public function testCurrentAfterRollback(): void
    {
        $this->migrationRunner->migrate();
        $this->migrationRunner->rollback();

        $this->assertNull(
            $this->migrationRunner->getHistory()->current()
        );
    }

    public function testSchema(): void
    {
        $this->migrationRunner->getHistory();

        $this->assertSame(
            [],
            $this->forgeRegistry->use($this->db)
                ->build('migrations')
                ->clear()
                ->addColumn('id', [
                    'type' => IntegerType::class,
                    'autoIncrement' => true,
                ])
                ->addColumn('version', [
                    'type' => IntegerType::class,
                    'nullable' => true,
                ])
                ->addColumn('timestamp', [
                    'type' => DateTimeType::class,
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->setPrimaryKey('id')
                ->sql()
        );
    }

    public function testTimestamp(): void
    {
        $now = DateTime::now();

        $this->migrationRunner->migrate();

        $history = $this->migrationRunner->getHistory()->all();

        $this->assertGreaterThanOrEqual(
            $now,
            $this->typeParser->use('datetime')->parse($history[0]['timestamp'])
        );
    }
}

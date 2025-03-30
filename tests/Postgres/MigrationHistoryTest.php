<?php
declare(strict_types=1);

namespace Tests\Postgres;

use Fyre\DateTime\DateTime;
use Fyre\DB\Types\DateTimeType;
use Fyre\DB\Types\IntegerType;
use Fyre\DB\Types\StringType;
use PHPUnit\Framework\TestCase;

use function array_column;

final class MigrationHistoryTest extends TestCase
{
    use PostgresConnectionTrait;

    public function testAllAfterMigration(): void
    {
        $this->migrationRunner->migrate();

        $history = $this->migrationRunner->getHistory()->all();

        $this->assertSame(
            [
                '3_Test3',
                '2_Test2',
                '1_Test1',
            ],
            array_column($history, 'migration')
        );
    }

    public function testAllAfterRollback(): void
    {
        $this->migrationRunner->migrate();
        $this->migrationRunner->rollback(steps: 1);
        $this->migrationRunner->migrate();

        $history = $this->migrationRunner->getHistory()->all();

        $this->assertSame(
            [
                '3_Test3',
                '2_Test2',
                '1_Test1',
            ],
            array_column($history, 'migration')
        );

        $this->assertSame(
            [
                2,
                1,
                1,
            ],
            array_column($history, 'batch')
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
                ->addColumn('batch', [
                    'type' => IntegerType::class,
                ])
                ->addColumn('migration', [
                    'type' => StringType::class,
                ])
                ->addColumn('timestamp', [
                    'type' => DateTimeType::class,
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->setPrimaryKey('id')
                ->addIndex('batch')
                ->addIndex('migration', [
                    'unique' => true,
                ])
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

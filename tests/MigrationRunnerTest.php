<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\Migration\Migration,
    Fyre\Migration\MigrationRunner,
    PHPUnit\Framework\TestCase,
    Tests\ConnectionTrait;

use function
    array_column,
    array_keys;

final class MigrationRunnerTest extends TestCase
{

    use
        ConnectionTrait;

    public function testCurrentVersion(): void
    {
        $this->assertNull(
            MigrationRunner::currentVersion()
        );
    }

    public function testGetMigrations(): void
    {
        $migrations = MigrationRunner::getMigrations();

        $this->assertSame(
            [
                1,
                2,
                3
            ],
            array_keys($migrations)
        );

        $this->assertInstanceOf(
            Migration::class,
            $migrations[1]
        );
    }

    public function testMigrate(): void
    {
        MigrationRunner::migrate();

        $this->schema->clear();

        $tableSchema = $this->schema->describe('test');

        $this->assertTrue(
            $tableSchema->hasColumn('value1')
        );

        $this->assertTrue(
            $tableSchema->hasColumn('value2')
        );

        $this->assertSame(
            3,
            MigrationRunner::currentVersion()
        );

        $history = MigrationRunner::getHistory();

        $this->assertSame(
            [
                1,
                2,
                3
            ],
            array_column($history, 'version')
        );
    }

    public function testMigrateToVersion(): void
    {
        MigrationRunner::migrate(2);

        $this->schema->clear();

        $tableSchema = $this->schema->describe('test');

        $this->assertTrue(
            $tableSchema->hasColumn('value1')
        );

        $this->assertFalse(
            $tableSchema->hasColumn('value2')
        );

        $this->assertSame(
            2,
            MigrationRunner::currentVersion()
        );

        $history = MigrationRunner::getHistory();

        $this->assertSame(
            [
                1,
                2
            ],
            array_column($history, 'version')
        );
    }

    public function testMigrateFromVersion(): void
    {
        MigrationRunner::migrate(2);
        MigrationRunner::migrate();

        $this->schema->clear();

        $tableSchema = $this->schema->describe('test');

        $this->assertTrue(
            $tableSchema->hasColumn('value1')
        );

        $this->assertTrue(
            $tableSchema->hasColumn('value2')
        );

        $this->assertSame(
            3,
            MigrationRunner::currentVersion()
        );

        $history = MigrationRunner::getHistory();

        $this->assertSame(
            [
                1,
                2,
                3
            ],
            array_column($history, 'version')
        );
    }

    public function testRollback(): void
    {
        MigrationRunner::migrate();
        MigrationRunner::rollback();

        $this->schema->clear();

        $this->assertFalse(
            $this->schema->hasTable('test')
        );

        $this->assertNull(
            MigrationRunner::currentVersion()
        );

        $history = MigrationRunner::getHistory();

        $this->assertSame(
            [
                1,
                2,
                3,
                2,
                1,
                null
            ],
            array_column($history, 'version')
        );
    }

    public function testRollbackToVersion(): void
    {
        MigrationRunner::migrate();
        MigrationRunner::rollback(2);

        $this->schema->clear();

        $tableSchema = $this->schema->describe('test');

        $this->assertTrue(
            $tableSchema->hasColumn('value1')
        );

        $this->assertFalse(
            $tableSchema->hasColumn('value2')
        );

        $this->assertSame(
            2,
            MigrationRunner::currentVersion()
        );

        $history = MigrationRunner::getHistory();

        $this->assertSame(
            [
                1,
                2,
                3,
                2
            ],
            array_column($history, 'version')
        );
    }

}

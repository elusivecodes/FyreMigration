<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Migration\Exceptions\MigrationException;
use Fyre\Migration\MigrationRunner;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Migration_1;
use Tests\Mock\Migration_2;
use Tests\Mock\Migration_3;

use function array_column;

final class MigrationRunnerTest extends TestCase
{
    use ConnectionTrait;

    public function testCurrentVersion(): void
    {
        $this->assertNull(
            MigrationRunner::currentVersion()
        );
    }

    public function testGetMigration(): void
    {
        $this->assertInstanceOf(
            Migration_1::class,
            MigrationRunner::getMigration(1)
        );
    }

    public function testGetMigrations(): void
    {
        $this->assertSame(
            [
                1 => Migration_1::class,
                2 => Migration_2::class,
                3 => Migration_3::class,
            ],
            MigrationRunner::getMigrations()
        );
    }

    public function testGetNamespace(): void
    {
        $this->assertSame(
            'Tests\Mock\\',
            MigrationRunner::getNamespace()
        );
    }

    public function testHasMigration(): void
    {
        $this->assertTrue(
            MigrationRunner::hasMigration(2)
        );
    }

    public function testHasMigrationFalse(): void
    {
        $this->assertFalse(
            MigrationRunner::hasMigration(4)
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
                3,
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
                3,
            ],
            array_column($history, 'version')
        );
    }

    public function testMigrateInvalid(): void
    {
        $this->expectException(MigrationException::class);

        MigrationRunner::migrate(4);
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
                2,
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
                null,
            ],
            array_column($history, 'version')
        );
    }

    public function testRollbackInvalid(): void
    {
        $this->expectException(MigrationException::class);

        MigrationRunner::rollback(4);
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
                2,
            ],
            array_column($history, 'version')
        );
    }
}

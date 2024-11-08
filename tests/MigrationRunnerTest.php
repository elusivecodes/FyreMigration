<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Migration\Exceptions\MigrationException;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Migration_1;
use Tests\Mock\Migration_2;
use Tests\Mock\Migration_3;
use Tests\Mysql\MysqlConnectionTrait;

final class MigrationRunnerTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testGetMigration(): void
    {
        $this->assertInstanceOf(
            Migration_1::class,
            $this->migrationRunner->getMigration(1)
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
            $this->migrationRunner->getMigrations()
        );
    }

    public function testGetNamespace(): void
    {
        $this->assertSame(
            'Tests\Mock\\',
            $this->migrationRunner->getNamespace()
        );
    }

    public function testHasMigration(): void
    {
        $this->assertTrue(
            $this->migrationRunner->hasMigration(2)
        );
    }

    public function testHasMigrationFalse(): void
    {
        $this->assertFalse(
            $this->migrationRunner->hasMigration(4)
        );
    }

    public function testMigrate(): void
    {
        $this->assertSame(
            $this->migrationRunner,
            $this->migrationRunner->migrate()
        );

        $this->schema->clear();

        $this->assertTrue(
            $this->schema->hasTable('test1')
        );

        $this->assertTrue(
            $this->schema->hasTable('test2')
        );

        $this->assertTrue(
            $this->schema->hasTable('test3')
        );
    }

    public function testMigrateFromVersion(): void
    {
        $this->migrationRunner->migrate(2);
        $this->migrationRunner->migrate();

        $this->schema->clear();

        $this->assertTrue(
            $this->schema->hasTable('test1')
        );

        $this->assertTrue(
            $this->schema->hasTable('test2')
        );

        $this->assertTrue(
            $this->schema->hasTable('test3')
        );
    }

    public function testMigrateInvalid(): void
    {
        $this->expectException(MigrationException::class);

        $this->migrationRunner->migrate(4);
    }

    public function testMigrateToVersion(): void
    {
        $this->migrationRunner->migrate(2);

        $this->schema->clear();

        $this->assertTrue(
            $this->schema->hasTable('test1')
        );

        $this->assertTrue(
            $this->schema->hasTable('test2')
        );

        $this->assertFalse(
            $this->schema->hasTable('test3')
        );
    }

    public function testRollback(): void
    {
        $this->migrationRunner->migrate();

        $this->assertSame(
            $this->migrationRunner,
            $this->migrationRunner->rollback()
        );

        $this->schema->clear();

        $this->assertFalse(
            $this->schema->hasTable('test1')
        );
    }

    public function testRollbackInvalid(): void
    {
        $this->expectException(MigrationException::class);

        $this->migrationRunner->rollback(4);
    }

    public function testRollbackToVersion(): void
    {
        $this->migrationRunner->migrate();
        $this->migrationRunner->rollback(2);

        $this->schema->clear();

        $this->assertTrue(
            $this->schema->hasTable('test1')
        );

        $this->assertTrue(
            $this->schema->hasTable('test2')
        );

        $this->assertFalse(
            $this->schema->hasTable('test3')
        );
    }
}

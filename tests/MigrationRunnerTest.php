<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Mock\Migration_1_Test1;
use Tests\Mock\Migration_2_Test2;
use Tests\Mock\Migration_3_Test3;
use Tests\Mysql\MysqlConnectionTrait;

final class MigrationRunnerTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testGetMigrations(): void
    {
        $this->assertSame(
            [
                '1_Test1' => Migration_1_Test1::class,
                '2_Test2' => Migration_2_Test2::class,
                '3_Test3' => Migration_3_Test3::class,
            ],
            $this->migrationRunner->getMigrations()
        );
    }

    public function testGetNamespaces(): void
    {
        $this->assertSame(
            [
                'Tests\Mock\\',
            ],
            $this->migrationRunner->getNamespaces()
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
        $this->migrationRunner->migrate();
        $this->migrationRunner->rollback();
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

        $this->assertFalse(
            $this->schema->hasTable('test2')
        );

        $this->assertFalse(
            $this->schema->hasTable('test3')
        );
    }

    public function testRollbackSteps(): void
    {
        $this->migrationRunner->migrate();
        $this->migrationRunner->rollback(steps: 2);

        $this->schema->clear();

        $this->assertTrue(
            $this->schema->hasTable('test1')
        );

        $this->assertFalse(
            $this->schema->hasTable('test2')
        );

        $this->assertFalse(
            $this->schema->hasTable('test3')
        );
    }
}

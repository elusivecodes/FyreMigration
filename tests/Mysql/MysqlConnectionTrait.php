<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\Container\Container;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Mysql\MysqlConnection;
use Fyre\DB\TypeParser;
use Fyre\Forge\ForgeRegistry;
use Fyre\Loader\Loader;
use Fyre\Migration\MigrationRunner;
use Fyre\Schema\Schema;
use Fyre\Schema\SchemaRegistry;

use function getenv;

trait MysqlConnectionTrait
{
    protected Connection $db;

    protected ForgeRegistry $forgeRegistry;

    protected MigrationRunner $migrationRunner;

    protected Schema $schema;

    protected TypeParser $typeParser;

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(Loader::class);
        $container->singleton(TypeParser::class);
        $container->singleton(ConnectionManager::class);
        $container->singleton(SchemaRegistry::class);
        $container->singleton(ForgeRegistry::class);

        $this->typeParser = $container->use(TypeParser::class);
        $this->forgeRegistry = $container->use(ForgeRegistry::class);
        $this->migrationRunner = $container->use(MigrationRunner::class);

        $this->db = $container->use(ConnectionManager::class)->setConfig(ConnectionManager::DEFAULT, [
            'className' => MysqlConnection::class,
            'host' => getenv('MYSQL_HOST'),
            'username' => getenv('MYSQL_USERNAME'),
            'password' => getenv('MYSQL_PASSWORD'),
            'database' => getenv('MYSQL_DATABASE'),
            'port' => getenv('MYSQL_PORT'),
            'collation' => 'utf8mb4_unicode_ci',
            'charset' => 'utf8mb4',
            'compress' => true,
            'persist' => true,
        ])->use();

        $this->schema = $container->use(SchemaRegistry::class)->use($this->db);

        $container->use(Loader::class)->addNamespaces([
            'Tests\Mock' => 'tests/Mock',
        ]);

        $this->migrationRunner->setNamespace('\Tests\Mock');
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TABLE IF EXISTS migrations');
        $this->db->query('DROP TABLE IF EXISTS test1');
        $this->db->query('DROP TABLE IF EXISTS test2');
        $this->db->query('DROP TABLE IF EXISTS test3');
    }
}

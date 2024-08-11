<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Mysql\MysqlConnection;
use Fyre\Loader\Loader;
use Fyre\Migration\MigrationRunner;
use Fyre\Schema\Schema;
use Fyre\Schema\SchemaRegistry;

use function getenv;

trait MysqlConnectionTrait
{
    protected Connection $db;

    protected Schema $schema;

    protected function setUp(): void
    {
        Loader::clear();
        Loader::addNamespaces([
            'Tests\Mock' => 'tests/Mock',
        ]);

        MigrationRunner::clear();
        MigrationRunner::setNamespace('\Tests\Mock');

        ConnectionManager::clear();
        ConnectionManager::setConfig([
            'default' => [
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
            ],
        ]);

        $this->db = ConnectionManager::use();
        $this->schema = SchemaRegistry::getSchema($this->db);

        $this->db->query('DROP TABLE IF EXISTS migrations');
        $this->db->query('DROP TABLE IF EXISTS test1');
        $this->db->query('DROP TABLE IF EXISTS test2');
        $this->db->query('DROP TABLE IF EXISTS test3');
    }
}

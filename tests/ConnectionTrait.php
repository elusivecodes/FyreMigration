<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\DB\Connection,
    Fyre\DB\ConnectionManager,
    Fyre\DB\Handlers\MySQL\MySQLConnection,
    Fyre\Loader\Loader,
    Fyre\Migration\MigrationRunner,
    Fyre\Schema\Schema,
    Fyre\Schema\SchemaRegistry;

use function
    getenv;

trait ConnectionTrait
{

    protected Connection $db;

    protected Schema $schema;

    protected function setUp(): void
    {
        Loader::clear();
        Loader::addNamespaces([
            'Tests\Mock' => 'tests/Mock'
        ]);

        MigrationRunner::clear();
        MigrationRunner::setNamespace('Tests\Mock');

        ConnectionManager::clear();
        ConnectionManager::setConfig('default', [
            'className' => MySQLConnection::class,
            'host' => getenv('DB_HOST'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'database' => getenv('DB_NAME'),
            'port' => getenv('DB_PORT'),
            'collation' => 'utf8mb4_unicode_ci',
            'charset' => 'utf8mb4',
            'compress' => true,
            'persist' => true
        ]);

        $this->db = ConnectionManager::use();
        $this->schema = SchemaRegistry::getSchema($this->db);

        $this->db->query('DROP TABLE IF EXISTS migrations');
        $this->db->query('DROP TABLE IF EXISTS test');
    }

}

<?php
declare(strict_types=1);

namespace Tests\Sqlite;

use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Sqlite\SqliteConnection;
use Fyre\Loader\Loader;
use Fyre\Migration\MigrationRunner;
use Fyre\Schema\Schema;
use Fyre\Schema\SchemaRegistry;

trait SqliteConnectionTrait
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
                'className' => SqliteConnection::class,
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

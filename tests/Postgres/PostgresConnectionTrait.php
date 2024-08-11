<?php
declare(strict_types=1);

namespace Tests\Postgres;

use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Postgres\PostgresConnection;
use Fyre\Loader\Loader;
use Fyre\Migration\MigrationRunner;
use Fyre\Schema\Schema;
use Fyre\Schema\SchemaRegistry;

use function getenv;

trait PostgresConnectionTrait
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
                'className' => PostgresConnection::class,
                'host' => getenv('POSTGRES_HOST'),
                'username' => getenv('POSTGRES_USERNAME'),
                'password' => getenv('POSTGRES_PASSWORD'),
                'database' => getenv('POSTGRES_DATABASE'),
                'port' => getenv('POSTGRES_PORT'),
                'charset' => 'utf8',
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

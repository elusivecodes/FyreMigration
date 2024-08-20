<?php
declare(strict_types=1);

namespace Fyre\Migration;

use Fyre\DB\Connection;
use Fyre\DB\Handlers\Mysql\MysqlConnection;
use Fyre\DB\Handlers\Postgres\PostgresConnection;
use Fyre\DB\Handlers\Sqlite\SqliteConnection;
use Fyre\Migration\Exceptions\MigrationException;
use Fyre\Migration\Handlers\Mysql\MysqlMigrationHistory;
use Fyre\Migration\Handlers\Postgres\PostgresMigrationHistory;
use Fyre\Migration\Handlers\Sqlite\SqliteMigrationHistory;
use WeakMap;

use function array_key_exists;
use function array_shift;
use function class_parents;
use function get_class;
use function ltrim;

/**
 * MigrationHistoryRegistry
 */
abstract class MigrationHistoryRegistry
{
    protected static array $handlers = [
        MysqlConnection::class => MysqlMigrationHistory::class,
        PostgresConnection::class => PostgresMigrationHistory::class,
        SqliteConnection::class => SqliteMigrationHistory::class,
    ];

    protected static WeakMap $migrationHistories;

    /**
     * Get the MigrationHistory for a Connection.
     *
     * @param Connection $connection The Connection.
     * @return MigrationHistory The MigrationHistory.
     */
    public static function getHistory(Connection $connection): MigrationHistory
    {
        static::$migrationHistories ??= new WeakMap();

        return static::$migrationHistories[$connection] ??= static::loadHistory($connection);
    }

    /**
     * Set a MigrationHistory handler for a Connection class.
     *
     * @param string $connectionClass The Connection class.
     * @param string $historyClass The MigrationHistory class.
     */
    public static function setHandler(string $connectionClass, string $historyClass): void
    {
        $connectionClass = ltrim($connectionClass, '\\');

        static::$handlers[$connectionClass] = $historyClass;
    }

    /**
     * Load a MigrationHistory for a Connection.
     *
     * @param Connection $connection The Connection.
     * @return MigrationHistory The MigrationHistory.
     *
     * @throws MigrationHistoryException if the handler is missing.
     */
    protected static function loadHistory(Connection $connection): MigrationHistory
    {
        $connectionClass = get_class($connection);
        $connectionKey = $connectionClass;

        while (!array_key_exists($connectionKey, static::$handlers)) {
            $classParents ??= class_parents($connection);
            $connectionKey = array_shift($classParents);

            if (!$connectionKey) {
                throw MigrationException::forMissingHandler($connectionClass);
            }
        }

        $historyClass = static::$handlers[$connectionClass];

        return new $historyClass($connection);
    }
}

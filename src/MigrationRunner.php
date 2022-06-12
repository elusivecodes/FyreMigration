<?php
declare(strict_types=1);

namespace Fyre\Migration;

use
    Fyre\DB\Connection,
    Fyre\DB\ConnectionManager,
    Fyre\FileSystem\Folder,
    Fyre\Forge\ForgeInterface,
    Fyre\Forge\ForgeRegistry,
    Fyre\Loader\Loader;

use const
    SORT_NUMERIC;

use function
    array_reverse,
    class_exists,
    is_subclass_of,
    ksort,
    trim;

/**
 * MigrationRunner
 */
class MigrationRunner
{

    protected static Connection|null $connection = null;

    protected static string $table = 'migrations';

    protected static string $namespace = '';

    protected static array|null $migrations = null;

    protected static bool $checked = false;

    /**
     * Clear loaded migrations.
     */
    public static function clear(): void
    {
        static::$connection = null;
        static::$migrations = null;
        static::$checked = false;
    }

    /**
     * Get the current version.
     * @return int|null The current version.
     */
    public static function currentVersion(): int|null
    {
        static::checkSchema();

        $result = static::$connection->builder()
            ->table(static::$table)
            ->select('version')
            ->orderBy([
                'id' => 'DESC'
            ])
            ->limit(1)
            ->execute()
            ->first();

        if (!$result || !$result['version']) {
            return null;
        }

        return (int) $result['version'];
    }

    /**
     * Get the Connection.
     * @return Connection The Connection.
     */
    public static function getConnection(): Connection
    {
        return static::$connection ??= ConnectionManager::use();
    }

    /**
     * Get the Forge.
     * @return ForgeInterface The Forge.
     */
    public static function getForge(): ForgeInterface
    {
        $connection = static::getConnection();

        return ForgeRegistry::getForge($connection);
    }

    /**
     * Get the migration history.
     * @return array The migration history.
     */
    public static function getHistory(): array
    {
        static::checkSchema();

        return static::$connection->builder()
            ->table(static::$table)
            ->select('*')
            ->execute()
            ->all();
    }

    /**
     * Get a Migration.
     * @param int $version The migration version.
     * @return Migration|null The Migration.
     */
    public static function getMigration(int $version): Migration|null
    {
        return static::getMigrations()[$version] ?? null;
    }

    /**
     * Get all migrations.
     * @return array The migrations.
     */
    public static function getMigrations(): array
    {
        return static::$migrations ??= static::findMigrations();
    }

    /**
     * Migrate to a version.
     * @param int|null $version The migration version.
     */
    public static function migrate(int|null $version = null): void
    {
        static::checkSchema();

        $current = static::currentVersion();
        $migrations = static::getMigrations();

        foreach ($migrations AS $migration) {
            $migrationVersion = $migration->version();

            if ($migrationVersion <= $current) {
                continue;
            }

            if ($version && $migrationVersion > $version) {
                break;
            }

            $migration->up();

            static::addHistory($migration);
        }
    }

    /**
     * Rollback to a version.
     * @param int|null $version The migration version.
     */
    public static function rollback(int|null $version = null)
    {
        static::checkSchema();

        $current = static::currentVersion();
        $migrations = static::getMigrations();

        $migrations = array_reverse($migrations);

        $nextMigration = null;

        foreach ($migrations AS $migration) {
            $migrationVersion = $migration->version();

            if ($migrationVersion > $current) {
                continue;
            }

            if ($version && $migrationVersion <= $version) {
                $nextMigration = $migration;
                break;
            }

            $migration->down();

            if ($migrationVersion < $current) {
                static::addHistory($migration);
            }
        }

        static::addHistory($nextMigration);
    }

    /**
     * Set the Connection.
     * @param Connection $connection The Connection.
     */
    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }

    /**
     * Set the namespace.
     * @param string $namespace The namespace.
     */
    public static function setNamespace(string $namespace): void
    {
        static::$namespace = static::normalizeNamespace($namespace);
    }

    /**
     * Add a Migration to the history.
     * @param Migration|null $migration The Migration.
     */
    protected static function addHistory(Migration|null $migration): void
    {
        static::$connection->builder()
            ->table(static::$table)
            ->insert([
                'version' => $migration ?
                    $migration->version() :
                    null
            ])
            ->execute();
    }

    /**
     * Check the migration schema.
     */
    protected static function checkSchema(): void
    {
        if (static::$checked) {
            return;
        }

        static::getForge()
            ->build(static::$table, [
                'clean' => true
            ])
            ->addColumn('id', [
                'type' => 'int',
                'unsigned' => true,
                'extra' => 'AUTO_INCREMENT'
            ])
            ->addColumn('version', [
                'type' => 'int',
                'nullable' => true
            ])
            ->addColumn('timestamp', [
                'type' => 'timestamp',
                'default' => 'CURRENT_TIMESTAMP()'
            ])
            ->setPrimaryKey('id')
            ->execute();

        static::$checked = true;
    }

    /**
     * Find the migration classes.
     * @return array The migration classes.
     */
    protected static function findMigrations(): array
    {
        $forge = static::getForge();
        $paths = Loader::getNamespace(static::$namespace);

        $migrations = [];
        foreach ($paths AS $path) {
            $folder = new Folder($path);
            $contents = $folder->contents();

            foreach ($contents AS $child) {
                if ($child instanceof Folder) {
                    continue;
                }

                if ($child->extension() !== 'php') {
                    continue;
                }

                $name = $child->fileName();

                $className = static::$namespace.$name;

                if (!class_exists($className) || !is_subclass_of($className, Migration::class)) {
                    continue;
                }

                $class = new $className($forge);
                $version = $class->version();

                $migrations[$version] = $class;
            }
        }

        ksort($migrations, SORT_NUMERIC);

        return $migrations;
    }

    /**
     * Normalize a namespace
     * @param string $namespace The namespace.
     * @return string The normalized namespace.
     */
    protected static function normalizeNamespace(string $namespace): string
    {
        return rtrim($namespace, '\\').'\\';
    }

}

<?php
declare(strict_types=1);

namespace Fyre\Migration;

use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\FileSystem\Folder;
use Fyre\Forge\Forge;
use Fyre\Forge\ForgeRegistry;
use Fyre\Loader\Loader;
use Fyre\Migration\Exceptions\MigrationException;
use Fyre\Utility\Path;

use const SORT_NUMERIC;

use function array_key_exists;
use function array_pop;
use function array_reverse;
use function array_unshift;
use function class_exists;
use function explode;
use function implode;
use function is_subclass_of;
use function ksort;
use function trim;

/**
 * MigrationRunner
 */
abstract class MigrationRunner
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
     * @return Forge The Forge.
     */
    public static function getForge(): Forge
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
        $migrations = static::getMigrations();

        if (!array_key_exists($version, $migrations)) {
            return null;
        }

        $forge = static::getForge();
        $migrationClass = $migrations[$version];

        return new $migrationClass($forge);
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
     * Get the namespace.
     * @return string The namespace.
     */
    public static function getNamespace(): string
    {
        return static::$namespace;
    }

    /**
     * Determine if a migration version exists.
     * @param int $version The migration version.
     * @return bool TRUE if the migration version exists, otherwise FALSE.
     */
    public static function hasMigration(int $version): bool
    {
        $migrations = static::getMigrations();

        return array_key_exists($version, $migrations);
    }

    /**
     * Migrate to a version.
     * @param int|null $version The migration version.
     */
    public static function migrate(int|null $version = null): void
    {
        $migrations = static::getMigrations();

        if ($version && !array_key_exists($version, $migrations)) {
            throw MigrationException::forInvalidVersion($version);
        }

        static::checkSchema();

        $current = static::currentVersion();

        if ($version && $version <= $current) {
            return;
        }

        $forge = static::getForge();

        foreach ($migrations AS $migrationVersion => $migrationClass) {
            if ($migrationVersion <= $current) {
                continue;
            }

            if ($version && $migrationVersion > $version) {
                break;
            }

            $migration = new $migrationClass($forge);

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
        $migrations = static::getMigrations();

        if ($version && !array_key_exists($version, $migrations)) {
            throw MigrationException::forInvalidVersion($version);
        }

        static::checkSchema();

        $current = static::currentVersion();

        if ($version && $version >= $current) {
            return;
        }

        $forge = static::getForge();
        $migrations = array_reverse($migrations, true);

        $nextMigration = null;

        foreach ($migrations AS $migrationVersion => $migrationClass) {
            if ($migrationVersion > $current) {
                continue;
            }

            $migration = new $migrationClass($forge);

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
     * Find all folders in namespaces.
     */
    protected static function findFolders(): array
    {
        $parts = explode('\\', static::$namespace);
        $pathParts = [];

        $folders = [];
        while ($parts !== []) {
            $namespace = implode('\\', $parts);
            $paths = Loader::getNamespacePaths($namespace);

            foreach ($paths AS $path) {
                $path = Path::join($path, ...$pathParts);
                $folder = new Folder($path);

                if (!$folder->exists()) {
                    continue;
                }

                $folders[] = $folder;
            }

            $lastPart = array_pop($parts);
            array_unshift($pathParts, $lastPart);
        }

        return $folders;
    }

    /**
     * Find the migration classes.
     * @return array The migration classes.
     */
    protected static function findMigrations(): array
    {
        $folders = static::findFolders();

        $migrations = [];
        foreach ($folders AS $folder) {
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

                $version = $className::version();

                $migrations[$version] = $className;
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
        return trim($namespace, '\\').'\\';
    }

}

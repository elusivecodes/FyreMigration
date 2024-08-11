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

use const SORT_NUMERIC;

/**
 * MigrationRunner
 */
abstract class MigrationRunner
{
    protected static Connection|null $connection = null;

    protected static MigrationHistory|null $history = null;

    protected static array|null $migrations = null;

    protected static string $namespace = '';

    /**
     * Clear loaded migrations.
     */
    public static function clear(): void
    {
        static::$connection = null;
        static::$history = null;
        static::$migrations = null;
    }

    /**
     * Get the Connection.
     *
     * @return Connection The Connection.
     */
    public static function getConnection(): Connection
    {
        return static::$connection ??= ConnectionManager::use();
    }

    /**
     * Get the Forge.
     *
     * @return Forge The Forge.
     */
    public static function getForge(): Forge
    {
        return ForgeRegistry::getForge(static::getConnection());
    }

    /**
     * Get the MigrationHistory.
     *
     * @return MigrationHistory MigrationHistory.
     */
    public static function getHistory(): MigrationHistory
    {
        return static::$history ??= MigrationHistoryRegistry::getHistory(static::getConnection());
    }

    /**
     * Get a Migration.
     *
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
     *
     * @return array The migrations.
     */
    public static function getMigrations(): array
    {
        return static::$migrations ??= static::findMigrations();
    }

    /**
     * Get the namespace.
     *
     * @return string The namespace.
     */
    public static function getNamespace(): string
    {
        return static::$namespace;
    }

    /**
     * Determine if a migration version exists.
     *
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
     *
     * @param int|null $version The migration version.
     *
     * @throws MigrationException if the version is not valid.
     */
    public static function migrate(int|null $version = null): void
    {
        $migrations = static::getMigrations();

        if ($version && !array_key_exists($version, $migrations)) {
            throw MigrationException::forInvalidVersion($version);
        }

        $current = static::getHistory()->current();

        if ($version && $version <= $current) {
            return;
        }

        $forge = static::getForge();

        foreach ($migrations as $migrationVersion => $migrationClass) {
            if ($migrationVersion <= $current) {
                continue;
            }

            if ($version && $migrationVersion > $version) {
                break;
            }

            $migration = new $migrationClass($forge);

            $migration->up();

            static::getHistory()->add($migration);
        }
    }

    /**
     * Rollback to a version.
     *
     * @param int|null $version The migration version.
     *
     * @throws MigrationException if the version is not valid.
     */
    public static function rollback(int|null $version = null): void
    {
        $migrations = static::getMigrations();

        if ($version && !array_key_exists($version, $migrations)) {
            throw MigrationException::forInvalidVersion($version);
        }

        $current = static::getHistory()->current();

        if ($version && $version >= $current) {
            return;
        }

        $forge = static::getForge();
        $migrations = array_reverse($migrations, true);

        $nextMigration = null;

        foreach ($migrations as $migrationVersion => $migrationClass) {
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
                static::getHistory()->add($migration);
            }
        }

        static::getHistory()->add($nextMigration);
    }

    /**
     * Set the Connection.
     *
     * @param Connection $connection The Connection.
     */
    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
        static::$history = null;
        static::$migrations = null;
    }

    /**
     * Set the namespace.
     *
     * @param string $namespace The namespace.
     */
    public static function setNamespace(string $namespace): void
    {
        static::$namespace = static::normalizeNamespace($namespace);
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

            foreach ($paths as $path) {
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
     *
     * @return array The migration classes.
     */
    protected static function findMigrations(): array
    {
        $folders = static::findFolders();

        $migrations = [];
        foreach ($folders as $folder) {
            $contents = $folder->contents();

            foreach ($contents as $child) {
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
     *
     * @param string $namespace The namespace.
     * @return string The normalized namespace.
     */
    protected static function normalizeNamespace(string $namespace): string
    {
        return trim($namespace, '\\').'\\';
    }
}

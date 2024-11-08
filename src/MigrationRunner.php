<?php
declare(strict_types=1);

namespace Fyre\Migration;

use Fyre\Container\Container;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\FileSystem\Folder;
use Fyre\Forge\Forge;
use Fyre\Forge\ForgeRegistry;
use Fyre\Loader\Loader;
use Fyre\Migration\Exceptions\MigrationException;
use Fyre\Utility\Path;
use ReflectionClass;

use function array_key_exists;
use function array_pop;
use function array_reverse;
use function array_unshift;
use function class_exists;
use function explode;
use function implode;
use function is_subclass_of;
use function ksort;
use function preg_match;
use function trim;

use const SORT_NUMERIC;

/**
 * MigrationRunner
 */
class MigrationRunner
{
    protected Connection|null $connection = null;

    protected ConnectionManager $connectionManager;

    protected Container $container;

    protected ForgeRegistry $forgeRegistry;

    protected MigrationHistory|null $history = null;

    protected Loader $loader;

    protected array|null $migrations = null;

    protected string $namespace = '';

    /**
     * New MigrationRunner constructor.
     *
     * @param Container $container The Container.
     * @param Loader $loader The Loader.
     * @param ConnectionManager $connectionManager The ConnectionManager.
     * @param ForgeRegistry $forgeRegistry The ForgeRegistry.
     */
    public function __construct(Container $container, Loader $loader, ConnectionManager $connectionManager, ForgeRegistry $forgeRegistry)
    {
        $this->container = $container;
        $this->loader = $loader;
        $this->connectionManager = $connectionManager;
        $this->forgeRegistry = $forgeRegistry;
    }

    /**
     * Clear loaded migrations.
     */
    public function clear(): void
    {
        $this->connection = null;
        $this->history = null;
        $this->migrations = null;
    }

    /**
     * Get the Connection.
     *
     * @return Connection The Connection.
     */
    public function getConnection(): Connection
    {
        return $this->connection ??= $this->connectionManager->use();
    }

    /**
     * Get the Forge.
     *
     * @return Forge The Forge.
     */
    public function getForge(): Forge
    {
        return $this->forgeRegistry->use($this->getConnection());
    }

    /**
     * Get the MigrationHistory.
     *
     * @return MigrationHistory MigrationHistory.
     */
    public function getHistory(): MigrationHistory
    {
        return $this->history ??= $this->container->build(MigrationHistory::class, [
            'connection' => $this->getConnection(),
        ]);
    }

    /**
     * Get a Migration.
     *
     * @param int $version The migration version.
     * @return Migration|null The Migration.
     */
    public function getMigration(int $version): Migration|null
    {
        $migrations = $this->getMigrations();

        if (!array_key_exists($version, $migrations)) {
            return null;
        }

        $forge = $this->getForge();
        $migrationClass = $migrations[$version];

        return $this->container->build($migrationClass, ['forge' => $this->getForge()]);
    }

    /**
     * Get all migrations.
     *
     * @return array The migrations.
     */
    public function getMigrations(): array
    {
        return $this->migrations ??= $this->findMigrations();
    }

    /**
     * Get the namespace.
     *
     * @return string The namespace.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Determine if a migration version exists.
     *
     * @param int $version The migration version.
     * @return bool TRUE if the migration version exists, otherwise FALSE.
     */
    public function hasMigration(int $version): bool
    {
        $migrations = $this->getMigrations();

        return array_key_exists($version, $migrations);
    }

    /**
     * Migrate to a version.
     *
     * @param int|null $version The migration version.
     * @return MigrationRunner The MigrationRunner.
     *
     * @throws MigrationException if the version is not valid.
     */
    public function migrate(int|null $version = null): static
    {
        $migrations = $this->getMigrations();

        if ($version && !array_key_exists($version, $migrations)) {
            throw MigrationException::forInvalidVersion($version);
        }

        $current = $this->getHistory()->current();

        if ($version && $version <= $current) {
            return $this;
        }

        foreach ($migrations as $migrationVersion => $migrationClass) {
            if ($migrationVersion <= $current) {
                continue;
            }

            if ($version && $migrationVersion > $version) {
                break;
            }

            $migration = $this->container->build($migrationClass, ['forge' => $this->getForge()]);

            if (method_exists($migration, 'up')) {
                $this->container->call([$migration, 'up']);
            }

            $this->getHistory()->add($migrationVersion);
        }

        return $this;
    }

    /**
     * Rollback to a version.
     *
     * @param int|null $version The migration version.
     * @return MigrationRunner The MigrationRunner.
     *
     * @throws MigrationException if the version is not valid.
     */
    public function rollback(int|null $version = null): static
    {
        $migrations = $this->getMigrations();

        if ($version && !array_key_exists($version, $migrations)) {
            throw MigrationException::forInvalidVersion($version);
        }

        $current = $this->getHistory()->current();

        if ($version && $version >= $current) {
            return $this;
        }

        $migrations = array_reverse($migrations, true);

        $nextMigration = null;

        foreach ($migrations as $migrationVersion => $migrationClass) {
            if ($migrationVersion > $current) {
                continue;
            }

            $migration = $this->container->build($migrationClass, ['forge' => $this->getForge()]);

            if ($version && $migrationVersion <= $version) {
                $nextMigration = $migrationVersion;
                break;
            }

            if (method_exists($migration, 'down')) {
                $this->container->call([$migration, 'down']);
            }

            if ($migrationVersion < $current) {
                $this->getHistory()->add($migrationVersion);
            }
        }

        $this->getHistory()->add($nextMigration);

        return $this;
    }

    /**
     * Set the Connection.
     *
     * @param Connection $connection The Connection.
     * @return MigrationRunner The MigrationRunner.
     */
    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;
        $this->history = null;
        $this->migrations = null;

        return $this;
    }

    /**
     * Set the namespace.
     *
     * @param string $namespace The namespace.
     * @return MigrationRunner The MigrationRunner.
     */
    public function setNamespace(string $namespace): static
    {
        $this->namespace = $this->normalizeNamespace($namespace);

        return $this;
    }

    /**
     * Find all folders in namespaces.
     */
    protected function findFolders(): array
    {
        $parts = explode('\\', $this->namespace);
        $pathParts = [];

        $folders = [];
        while ($parts !== []) {
            $namespace = implode('\\', $parts);
            $paths = $this->loader->getNamespacePaths($namespace);

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
    protected function findMigrations(): array
    {
        $folders = $this->findFolders();

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

                $className = $this->namespace.$name;

                if (!class_exists($className) || !is_subclass_of($className, Migration::class)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);
                $name = $reflection->getShortName();

                if (!preg_match('/^Migration_(\d+)$/', $name, $match)) {
                    throw MigrationException::forInvalidClassName($name);
                }

                $version = (int) $match[1];

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

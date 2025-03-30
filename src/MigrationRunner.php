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

use function array_column;
use function array_key_exists;
use function array_pop;
use function array_splice;
use function array_unshift;
use function class_exists;
use function explode;
use function implode;
use function in_array;
use function is_subclass_of;
use function ksort;
use function str_starts_with;
use function substr;
use function trim;

use const SORT_NATURAL;

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

    protected array $namespaces = [];

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
     * Add a namespace for loading models.
     *
     * @param string $namespace The namespace.
     * @return static The ModelRegistry.
     */
    public function addNamespace(string $namespace): static
    {
        $namespace = static::normalizeNamespace($namespace);

        if (!in_array($namespace, $this->namespaces)) {
            $this->namespaces[] = $namespace;
        }

        return $this;
    }

    /**
     * Clear loaded migrations.
     */
    public function clear(): void
    {
        $this->namespaces = [];
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
     * Get all migrations.
     *
     * @return array The migrations.
     */
    public function getMigrations(): array
    {
        return $this->migrations ??= $this->findMigrations();
    }

    /**
     * Get the namespaces.
     *
     * @return array The namespaces.
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Determine whether a namespace exists.
     *
     * @param string $namespace The namespace.
     * @return bool TRUE if the namespace exists, otherwise FALSE.
     */
    public function hasNamespace(string $namespace): bool
    {
        $namespace = static::normalizeNamespace($namespace);

        return in_array($namespace, $this->namespaces);
    }

    /**
     * Migrate to the latest version.
     *
     * @return MigrationRunner The MigrationRunner.
     */
    public function migrate(): static
    {
        $migrations = $this->getMigrations();

        $history = $this->getHistory();

        $ranMigrations = $history->all();
        $ranMigrationNames = array_column($ranMigrations, 'migration');

        $batch = $history->getNextBatch();

        foreach ($migrations as $migrationName => $className) {
            if (in_array($migrationName, $ranMigrationNames)) {
                continue;
            }

            $migration = $this->container->build($className, ['forge' => $this->getForge()]);

            if (method_exists($migration, 'up')) {
                $this->container->call([$migration, 'up']);
            }

            $history->add($migrationName, $batch);
        }

        return $this;
    }

    /**
     * Remove a namespace.
     *
     * @param string $namespace The namespace.
     * @return static The ModelRegistry.
     */
    public function removeNamespace(string $namespace): static
    {
        $namespace = static::normalizeNamespace($namespace);

        foreach ($this->namespaces as $i => $otherNamespace) {
            if ($otherNamespace !== $namespace) {
                continue;
            }

            array_splice($this->namespaces, $i, 1);
            break;
        }

        return $this;
    }

    /**
     * Rollback to a previous version.
     *
     * @param int|null $batches The number of batches to rollback.
     * @param int|null $steps The number of steps to rollback.
     * @return MigrationRunner The MigrationRunner.
     */
    public function rollback(int|null $batches = 1, int|null $steps = null): static
    {
        $migrations = $this->getMigrations();

        $history = $this->getHistory();

        $ranMigrations = $history->all();

        $lastBatch = null;

        foreach ($ranMigrations as $data) {
            if ($batches !== null && $data['batch'] !== $lastBatch && $batches-- <= 0) {
                break;
            }

            if ($steps !== null && $steps-- <= 0) {
                break;
            }

            $migrationName = $data['migration'];
            $lastBatch = $data['batch'];

            if (array_key_exists($migrationName, $migrations)) {
                $migration = $this->container->build($migrations[$migrationName], ['forge' => $this->getForge()]);

                if (method_exists($migration, 'down')) {
                    $this->container->call([$migration, 'down']);
                }
            }

            $history->delete($migrationName);
        }

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
     * Find all folders in namespaces.
     */
    protected function findFolders(string $namespace): array
    {
        $parts = explode('\\', $namespace);
        $pathParts = [];

        $folders = [];
        while ($parts !== []) {
            $currentNamespace = implode('\\', $parts);
            $paths = $this->loader->getNamespacePaths($currentNamespace);

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
        $migrations = [];
        foreach ($this->namespaces as $namespace) {
            $folders = $this->findFolders($namespace);

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

                    $className = $namespace.$name;

                    if (!class_exists($className) || !is_subclass_of($className, Migration::class)) {
                        continue;
                    }

                    $reflection = new ReflectionClass($className);
                    $name = $reflection->getShortName();

                    if (!str_starts_with($name, 'Migration_')) {
                        throw MigrationException::forInvalidClassName($name);
                    }

                    $name = substr($name, 10);

                    $migrations[$name] = $className;
                }
            }
        }

        ksort($migrations, SORT_NATURAL);

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

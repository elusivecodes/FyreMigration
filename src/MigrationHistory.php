<?php

namespace Fyre\Migration;

use Fyre\DB\Connection;
use Fyre\DB\Types\DateTimeType;
use Fyre\DB\Types\IntegerType;
use Fyre\Forge\ForgeRegistry;

/**
 * MigrationHistory
 */
class MigrationHistory
{
    protected static string $table = 'migrations';

    protected Connection $connection;

    protected ForgeRegistry $forgeRegistry;

    /**
     * New MigrationHistory constructor.
     *
     * @param Connection $connection The Connection.
     * @param ForgeRegistry $forgeRegistry The ForgeRegistry.
     */
    public function __construct(Connection $connection, ForgeRegistry $forgeRegistry)
    {
        $this->connection = $connection;
        $this->forgeRegistry = $forgeRegistry;

        $this->check();
    }

    /**
     * Add a migration version to the history.
     *
     * @param int|null $version The migration version.
     */
    public function add(int|null $version): void
    {
        $this->connection
            ->insert()
            ->into(static::$table)
            ->values([
                [
                    'version' => $version,
                ],
            ])
            ->execute();
    }

    /**
     * Get the migration history.
     *
     * @return array The migration history.
     */
    public function all(): array
    {
        return $this->connection
            ->select()
            ->from(static::$table)
            ->execute()
            ->all();
    }

    /**
     * Get the current version.
     *
     * @return int|null The current version.
     */
    public function current(): int|null
    {
        $result = $this->connection
            ->select([
                'version',
            ])
            ->from(static::$table)
            ->orderBy([
                'id' => 'DESC',
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
     * Check the migration schema.
     */
    /**
     * Check the migration schema.
     */
    protected function check(): void
    {
        $this->forgeRegistry->use($this->connection)
            ->build(static::$table)
            ->clear()
            ->addColumn('id', [
                'type' => IntegerType::class,
                'autoIncrement' => true,
            ])
            ->addColumn('version', [
                'type' => IntegerType::class,
                'nullable' => true,
            ])
            ->addColumn('timestamp', [
                'type' => DateTimeType::class,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->setPrimaryKey('id')
            ->execute();
    }
}

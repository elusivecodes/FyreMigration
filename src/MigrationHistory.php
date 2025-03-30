<?php

namespace Fyre\Migration;

use Fyre\DB\Connection;
use Fyre\DB\Types\DateTimeType;
use Fyre\DB\Types\IntegerType;
use Fyre\DB\Types\StringType;
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
     * Add a migration to the history.
     *
     * @param string $name The migration name.
     * @param int $batch The batch number.
     */
    public function add(string $name, int $batch): void
    {
        $this->connection
            ->insert()
            ->into(static::$table)
            ->values([
                [
                    'batch' => $batch,
                    'migration' => $name,
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
            ->orderBy([
                'batch' => 'DESC',
                'id' => 'DESC',
            ])
            ->execute()
            ->all();
    }

    /**
     * Delete a migration from the history.
     *
     * @param string $name The migration name.
     */
    public function delete(string $name): void
    {
        $this->connection
            ->delete()
            ->from(static::$table)
            ->where([
                'migration' => $name,
            ])
            ->execute();
    }

    /**
     * Get the next batch number.
     *
     * @return int The next batch number.
     */
    public function getNextBatch(): int
    {
        $lastBatch = $this->connection
            ->select([
                'last_batch' => 'MAX(batch)',
            ])
            ->from(static::$table)
            ->execute()
            ->fetch()['last_batch'] ?? 0;

        return $lastBatch + 1;
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
            ->addColumn('batch', [
                'type' => IntegerType::class,
            ])
            ->addColumn('migration', [
                'type' => StringType::class,
            ])
            ->addColumn('timestamp', [
                'type' => DateTimeType::class,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->setPrimaryKey('id')
            ->addIndex('batch')
            ->addIndex('migration', [
                'unique' => true,
            ])
            ->execute();
    }
}

<?php

namespace Fyre\Migration;

use Fyre\DB\Connection;

/**
 * MigrationHistory
 */
abstract class MigrationHistory
{
    protected static string $table = 'migrations';

    protected Connection $connection;

    /**
     * New MigrationHistory constructor.
     *
     * @param Connection $connection The Connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->check();
    }

    /**
     * Add a Migration to the history.
     *
     * @param Migration|null $migration The Migration.
     */
    public function add(Migration|null $migration): void
    {
        $this->connection
            ->insert()
            ->into(static::$table)
            ->values([
                [
                    'version' => $migration ?
                        $migration->version() :
                        null,
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
    abstract protected function check(): void;
}

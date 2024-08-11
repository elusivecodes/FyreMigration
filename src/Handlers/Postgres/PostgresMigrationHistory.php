<?php

namespace Fyre\Migration\Handlers\Postgres;

use Fyre\Forge\ForgeRegistry;
use Fyre\Migration\MigrationHistory;

/**
 * PostgresMigrationHistory
 */
class PostgresMigrationHistory extends MigrationHistory
{
    /**
     * Check the migration schema.
     */
    protected function check(): void
    {
        ForgeRegistry::getForge($this->connection)
            ->build(static::$table)
            ->clear()
            ->addColumn('id', [
                'type' => 'integer',
                'autoIncrement' => true,
            ])
            ->addColumn('version', [
                'type' => 'integer',
                'nullable' => true,
            ])
            ->addColumn('timestamp', [
                'type' => 'timestamp',
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->setPrimaryKey('id')
            ->execute();
    }
}

<?php

namespace Fyre\Migration\Handlers\Mysql;

use Fyre\Forge\ForgeRegistry;
use Fyre\Migration\MigrationHistory;

/**
 * MysqlMigrationHistory
 */
class MysqlMigrationHistory extends MigrationHistory
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
                'type' => 'int',
                'autoIncrement' => true,
            ])
            ->addColumn('version', [
                'type' => 'int',
                'nullable' => true,
            ])
            ->addColumn('timestamp', [
                'type' => 'timestamp',
                'default' => 'current_timestamp()',
            ])
            ->setPrimaryKey('id')
            ->execute();
    }
}

<?php
declare(strict_types=1);

namespace Fyre\Migration\Commands;

use Fyre\Command\Command;
use Fyre\DB\ConnectionManager;
use Fyre\Migration\MigrationRunner;

/**
 * RollbackCommand
 */
class RollbackCommand extends Command
{
    protected string|null $alias = 'db:rollback';

    protected string $description = 'Perform database rollbacks.';

    protected array $options = [
        'db' => [
            'default' => ConnectionManager::DEFAULT,
        ],
        'batches' => [
            'as' => 'integer',
            'default' => 1,
        ],
        'steps' => [
            'as' => 'integer',
            'default' => null,
        ],
    ];

    /**
     * Run the command.
     *
     * @param ConnectionManager $connectionManager The ConnectionManager.
     * @param MigrationRunner $migrationRunner The MigrationRunner.
     * @param string $db The connection key.
     * @param int|null $batches The number of batches to rollback.
     * @param int $steps The number of steps  to rollback.
     * @return int|null The exit code.
     */
    public function run(ConnectionManager $connectionManager, MigrationRunner $migrationRunner, string $db, int|null $batches = 1, int|null $steps = null): int|null
    {
        $connection = $connectionManager->use($db);

        $migrationRunner
            ->setConnection($connection)
            ->rollback($batches, $steps);

        return static::CODE_SUCCESS;
    }
}

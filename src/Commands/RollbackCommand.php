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
        'version' => [
            'default' => null,
        ],
    ];

    /**
     * Run the command.
     *
     * @param ConnectionManager $connectionManager The ConnectionManager.
     * @param MigrationRunner $migrationRunner The MigrationRunner.
     * @param string|null $db The connection key.
     * @param int|null $version The migration version.
     * @return int|null The exit code.
     */
    public function run(ConnectionManager $connectionManager, MigrationRunner $migrationRunner, string|null $db = null, int|null $version = null): int|null
    {
        if ($db) {
            $connection = $connectionManager->use($db);
            $migrationRunner->setConnection($connection);
        }

        $migrationRunner->rollback($version);

        return static::CODE_SUCCESS;
    }
}

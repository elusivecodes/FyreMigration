<?php
declare(strict_types=1);

namespace Fyre\Migration\Commands;

use Fyre\Command\Command;
use Fyre\DB\ConnectionManager;
use Fyre\Migration\MigrationRunner;

/**
 * MigrateCommand
 */
class MigrateCommand extends Command
{
    protected string|null $alias = 'db:migrate';

    protected string $description = 'Perform database migrations.';

    protected array $options = [
        'db' => [
            'default' => ConnectionManager::DEFAULT,
        ],
    ];

    /**
     * Run the command.
     *
     * @param ConnectionManager $connectionManager The ConnectionManager.
     * @param MigrationRunner $migrationRunner The MigrationRunner.
     * @param string $db The connection key.
     * @return int|null The exit code.
     */
    public function run(ConnectionManager $connectionManager, MigrationRunner $migrationRunner, string $db): int|null
    {
        $connection = $connectionManager->use($db);

        $migrationRunner
            ->setConnection($connection)
            ->migrate();

        return static::CODE_SUCCESS;
    }
}

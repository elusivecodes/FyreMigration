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

    protected string $description = 'This command will perform DB migrations.';

    protected string|null $name = 'Migrate';

    /**
     * Run the command.
     *
     * @param array $arguments The command arguments.
     * @return int|null The exit code.
     */
    public function run(array $arguments = []): int|null
    {
        $arguments['connection'] ??= null;

        if ($arguments['connection']) {
            $connection = ConnectionManager::use($arguments['connection']);
            MigrationRunner::setConnection($connection);
        }

        MigrationRunner::migrate($arguments['version'] ?? null);

        return static::CODE_SUCCESS;
    }
}

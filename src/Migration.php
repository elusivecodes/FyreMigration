<?php
declare(strict_types=1);

namespace Fyre\Migration;

use Fyre\Forge\Forge;
use Fyre\Migration\Exceptions\MigrationException;
use ReflectionClass;

use function preg_match;

/**
 * Migration
 */
abstract class Migration
{
    protected Forge $forge;

    /**
     * Get the migration version.
     *
     * @return int The migration version.
     *
     * @throws MigrationException if the class name is not valid.
     */
    public static function version(): int
    {
        $reflect = new ReflectionClass(static::class);
        $name = $reflect->getShortName();

        if (!preg_match('/^Migration_(\d+)$/', $name, $match)) {
            throw MigrationException::forInvalidClassName($name);
        }

        return (int) $match[1];
    }

    /**
     * New Migration constructor.
     *
     * @param Forge $forge The Forge.
     */
    public function __construct(Forge $forge)
    {
        $this->forge = $forge;
    }

    /**
     * Perform a "down" migration.
     */
    public function down(): void {}

    /**
     * Perform an "up" migration.
     */
    public function up(): void {}
}

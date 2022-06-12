<?php
declare(strict_types=1);

namespace Fyre\Migration;

use
    Fyre\Forge\ForgeInterface,
    ReflectionClass,
    RuntimeException;

use function
    preg_match;

/**
 * Migration
 */
abstract class Migration
{

    protected ForgeInterface $forge;

    protected int $version;

    /**
     * New Migration constructor.
     */
    public function __construct(ForgeInterface $forge)
    {
        $this->forge = $forge;

        $reflect = new ReflectionClass($this);
        $name = $reflect->getShortName();

        if (!preg_match('/^Migration_(\d+)$/', $name, $match)) {
            throw new RuntimeException('Invalid migration class name: '.$name);
        }

        $this->version = (int) $match[1];
    }

    /**
     * Perform a "down" migration.
     */
    public function down(): void
    {
        
    }

    /**
     * Perform an "up" migration.
     */
    public function up(): void
    {
        
    }

    /**
     * Get the migration version.
     * @return int The migration version.
     */
    public function version(): int
    {
        return $this->version;
    }

}

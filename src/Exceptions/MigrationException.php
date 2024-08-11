<?php
declare(strict_types=1);

namespace Fyre\Migration\Exceptions;

use RuntimeException;

/**
 * MigrationException
 */
class MigrationException extends RuntimeException
{
    public static function forInvalidClassName(string $name = ''): static
    {
        return new static('Migration invalid class name: '.$name);
    }

    public static function forInvalidVersion(int $version): static
    {
        return new static('Migration invalid version: '.$version);
    }

    public static function forMissingHandler(string $name): static
    {
        return new static('Missing handler for connection handler: '.$name);
    }
}

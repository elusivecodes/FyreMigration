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
}

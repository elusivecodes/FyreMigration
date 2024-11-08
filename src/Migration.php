<?php
declare(strict_types=1);

namespace Fyre\Migration;

use Fyre\Forge\Forge;

/**
 * Migration
 */
abstract class Migration
{
    protected Forge $forge;

    /**
     * New Migration constructor.
     *
     * @param Forge $forge The Forge.
     */
    public function __construct(Forge $forge)
    {
        $this->forge = $forge;
    }
}

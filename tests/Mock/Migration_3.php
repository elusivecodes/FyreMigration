<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Migration\Migration;

class Migration_3 extends Migration
{
    public function down(): void
    {
        $this->forge->dropTable('test3');
    }

    public function up(): void
    {
        $this->forge->createTable('test3', [
            'value' => [],
        ]);
    }
}

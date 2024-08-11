<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Migration\Migration;

class Migration_2 extends Migration
{
    public function down(): void
    {
        $this->forge->dropTable('test2');
    }

    public function up(): void
    {
        $this->forge->createTable('test2', [
            'value' => [],
        ]);
    }
}

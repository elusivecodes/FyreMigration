<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Migration\Migration;

class Migration_1 extends Migration
{
    public function down(): void
    {
        $this->forge->dropTable('test1');
    }

    public function up(): void
    {
        $this->forge->createTable('test1', [
            'value' => [],
        ]);
    }
}

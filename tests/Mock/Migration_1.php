<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Migration\Migration;

class Migration_1 extends Migration
{

    public function down(): void
    {
        $this->forge->dropTable('test');
    }

    public function up(): void
    {
        $this->forge->createTable('test', [
            'id' => [
                'type' => 'int'
            ]
        ]);
    }

}

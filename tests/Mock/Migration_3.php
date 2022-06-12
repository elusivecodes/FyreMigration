<?php
declare(strict_types=1);

namespace Tests\Mock;

use
    Fyre\Migration\Migration;

class Migration_3 extends Migration
{

    public function down(): void
    {
        $this->forge->dropColumn('test', 'value2');
    }

    public function up(): void
    {
        $this->forge->addColumn('test', 'value2');
    }

}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTotalQuantityToPurchaseOrders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('purchase_orders', [
            'total_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
                'after' => 'total_amount'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('purchase_orders', 'total_quantity');
    }
}

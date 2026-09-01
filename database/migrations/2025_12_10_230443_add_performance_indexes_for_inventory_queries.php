<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Índices críticos para optimizar consultas que reemplazan vistas de BD:
     * - vw_inventory_unified
     * - vw_inventory_available
     * - vw_salidas_pendientes_devolucion
     */
    public function up(): void
    {
        // Índices para inventory_outputs
        $this->createIndexIfMissing('inventory_outputs', 'idx_inventory_outputs_lookup', 'inventory_id, status, quantity');
        $this->createIndexIfMissing('inventory_outputs', 'idx_inventory_outputs_customer_warehouse', 'customer, warehouse, status');
        $this->createIndexIfMissing('inventory_outputs', 'idx_inventory_outputs_date', 'created_at, status');

        // Índices para inventories
        $this->createIndexIfMissing('inventories', 'idx_inventories_item_location_lookup', 'item_id, location_id, warehouse, customer, status');
        $this->createIndexIfMissing('inventories', 'idx_inventories_sku_lookup', 'sku, customer, warehouse');
        $this->createIndexIfMissing('inventories', 'idx_inventories_retention', 'status, retention_substatus, customer');
        $this->createIndexIfMissing('inventories', 'idx_inventories_location_code', 'localizacion, warehouse, customer');

        // Índices para picking_reservations
        $this->createIndexIfMissing('picking_reservations', 'idx_picking_reservations_inventory', 'inventory_id, quantity_reserved');
        $this->createIndexIfMissing('picking_reservations', 'idx_picking_reservations_order', 'picking_order_id, inventory_id');

        // Índices para picking_orders
        $this->createIndexIfMissing('picking_orders', 'idx_picking_orders_status', 'id, status');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('inventory_outputs', 'idx_inventory_outputs_lookup');
        $this->dropIndexIfExists('inventory_outputs', 'idx_inventory_outputs_customer_warehouse');
        $this->dropIndexIfExists('inventory_outputs', 'idx_inventory_outputs_date');

        $this->dropIndexIfExists('inventories', 'idx_inventories_item_location_lookup');
        $this->dropIndexIfExists('inventories', 'idx_inventories_sku_lookup');
        $this->dropIndexIfExists('inventories', 'idx_inventories_retention');
        $this->dropIndexIfExists('inventories', 'idx_inventories_location_code');

        $this->dropIndexIfExists('picking_reservations', 'idx_picking_reservations_inventory');
        $this->dropIndexIfExists('picking_reservations', 'idx_picking_reservations_order');

        $this->dropIndexIfExists('picking_orders', 'idx_picking_orders_status');
    }

    private function createIndexIfMissing(string $table, string $index, string $columns): void
    {
        if (!$this->indexExists($table, $index)) {
            DB::statement("CREATE INDEX `{$index}` ON `{$table}` ({$columns})");
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("DROP INDEX `{$index}` ON `{$table}`");
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};

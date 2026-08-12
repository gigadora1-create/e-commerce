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
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventory_outputs_lookup ON inventory_outputs(inventory_id, status, quantity)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventory_outputs_customer_warehouse ON inventory_outputs(customer, warehouse, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventory_outputs_date ON inventory_outputs(created_at, status)');

        // Índices para inventories
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventories_item_location_lookup ON inventories(item_id, location_id, warehouse, customer, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventories_sku_lookup ON inventories(sku, customer, warehouse)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventories_retention ON inventories(status, retention_substatus, customer)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventories_location_code ON inventories(localizacion, warehouse, customer)');

        // Índices para picking_reservations
        DB::statement('CREATE INDEX IF NOT EXISTS idx_picking_reservations_inventory ON picking_reservations(inventory_id, quantity_reserved)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_picking_reservations_order ON picking_reservations(picking_order_id, inventory_id)');

        // Índices para picking_orders
        DB::statement('CREATE INDEX IF NOT EXISTS idx_picking_orders_status ON picking_orders(id, status)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_inventory_outputs_lookup ON inventory_outputs');
        DB::statement('DROP INDEX IF EXISTS idx_inventory_outputs_customer_warehouse ON inventory_outputs');
        DB::statement('DROP INDEX IF EXISTS idx_inventory_outputs_date ON inventory_outputs');

        DB::statement('DROP INDEX IF EXISTS idx_inventories_item_location_lookup ON inventories');
        DB::statement('DROP INDEX IF EXISTS idx_inventories_sku_lookup ON inventories');
        DB::statement('DROP INDEX IF EXISTS idx_inventories_retention ON inventories');
        DB::statement('DROP INDEX IF EXISTS idx_inventories_location_code ON inventories');

        DB::statement('DROP INDEX IF EXISTS idx_picking_reservations_inventory ON picking_reservations');
        DB::statement('DROP INDEX IF EXISTS idx_picking_reservations_order ON picking_reservations');

        DB::statement('DROP INDEX IF EXISTS idx_picking_orders_status ON picking_orders');
    }
};

<?php

namespace Tests\Unit;

use App\Exports\PickingRequestTemplateExport;
use App\Imports\PickingRequestImport;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PickingRequestImportTest extends TestCase
{
    public function test_template_does_not_request_a_product_name(): void
    {
        $this->assertSame(
            ['SKU', 'Cantidad', 'Bodega', 'Cliente', 'Pedido'],
            (new PickingRequestTemplateExport())->headings()
        );
    }

    public function test_product_name_is_read_from_inventory_when_excel_omits_it(): void
    {
        $import = new TestablePickingRequestImport();
        $import->setInventory('SKU-1', 'Principal', 'Cliente 1', 'Producto del inventario');

        $import->process(collect([
            'sku' => 'SKU-1',
            'cantidad' => 2,
            'bodega' => 'Principal',
            'cliente' => 'Cliente 1',
        ]));

        $this->assertSame([], $import->getErrors());
        $this->assertSame('Producto del inventario', $import->getItems()[0]['item_name']);
    }

    public function test_legacy_product_column_does_not_override_inventory_name(): void
    {
        $import = new TestablePickingRequestImport();
        $import->setInventory('SKU-1', 'Principal', 'Cliente 1', 'Producto del inventario');

        $import->process(collect([
            'sku' => 'SKU-1',
            'producto' => 'Nombre anterior',
            'cantidad' => 2,
            'bodega' => 'Principal',
            'cliente' => 'Cliente 1',
        ]));

        $this->assertSame([], $import->getErrors());
        $this->assertSame('Producto del inventario', $import->getItems()[0]['item_name']);
    }
}

class TestablePickingRequestImport extends PickingRequestImport
{
    public function setInventory(string $sku, string $warehouse, string $customer, string $description): void
    {
        $inventory = (object) [
            'id' => 1,
            'item_description' => $description,
            'localizacion' => 'A-1',
            'expiry_date' => '2027-12-31',
            'quantity_net_available' => 10,
        ];

        $key = "$sku|$warehouse|$customer";
        $this->allSkusInSystem[$sku] = [['warehouse' => $warehouse, 'customer' => $customer]];
        $this->inventoryCache[$key] = $inventory;
        $this->availabilityCache[$key] = collect([$inventory]);
    }

    public function process(Collection $row): void
    {
        $this->processRow($row, 2);
    }
}

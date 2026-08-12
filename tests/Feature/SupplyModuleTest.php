<?php

namespace Tests\Feature;

use App\Mail\SupplyPurchaseRequestCreated;
use App\Models\SupplyClient;
use App\Models\SupplyProduct;
use App\Models\SupplyPurchaseRecipient;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\User;
use App\Services\Supply\SupplyPurchaseNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('auth.two_factor_enabled', false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (!Schema::hasTable('supply_purchase_recipients')) {
            Schema::create('supply_purchase_recipients', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function test_internal_user_can_access_supplies_without_customer_context(): void
    {
        Permission::findOrCreate('supplies.admin', 'web');
        Permission::findOrCreate('supplies.request', 'web');
        Role::findOrCreate('PROVEEDURIA_ADMIN', 'web')->syncPermissions(['supplies.admin', 'supplies.request']);

        $user = User::factory()->create([
            'email' => 'supplies-access@example.com',
        ]);
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        $this->actingAs($user)
            ->get(route('supplies.index'))
            ->assertOk()
            ->assertSee('Modulo de proveeduria');
    }

    public function test_warehouse_only_user_cannot_access_supplies_module(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');

        $user = User::factory()->create([
            'email' => 'warehouse-supplies-blocked@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);

        $this->actingAs($user)
            ->withSession(['selected_customer' => 'BODEGA TEST'])
            ->get(route('supplies.index'))
            ->assertRedirect(route('warehouse.index'));
    }

    public function test_user_can_create_and_audit_supply_request(): void
    {
        Permission::findOrCreate('supplies.admin', 'web');
        Permission::findOrCreate('supplies.request', 'web');
        Role::findOrCreate('PROVEEDURIA_ADMIN', 'web')->syncPermissions(['supplies.admin', 'supplies.request']);

        $user = User::factory()->create([
            'email' => 'supplies-flow@example.com',
        ]);
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        $product = SupplyProduct::query()->firstOrFail();
        $this->actingAs($user)
            ->post(route('supplies.requests.store'), [
                'product_id' => [$product->id],
                'requested_quantity' => [5],
                'request_notes' => 'Solicitud de prueba',
            ])
            ->assertRedirect(route('supplies.index', ['tab' => 'requests']));

        $requestRecord = SupplyRequest::query()->with('items')->latest('id')->firstOrFail();

        $this->assertSame(SupplyRequest::STATUS_REQUESTED, $requestRecord->status);
        $this->assertNull($requestRecord->supply_client_id);
        $this->assertCount(1, $requestRecord->items);

        $item = $requestRecord->items->first();

        $this->actingAs($user)
            ->put(route('supplies.requests.audit', $requestRecord), [
                'received_by_name' => 'Usuario Receptor',
                'delivered_by_name' => 'Usuario Entrega',
                'audit_notes' => 'Recibido completo',
                'received_quantity' => [
                    $item->id => 5,
                ],
                'observation' => [
                    $item->id => 'Sin novedad',
                ],
            ])
            ->assertRedirect(route('supplies.show', $requestRecord));

        $requestRecord->refresh();
        $item->refresh();

        $this->assertSame(SupplyRequest::STATUS_COMPLETE, $requestRecord->status);
        $this->assertSame(5, $item->received_quantity);
        $this->assertSame(0, $item->missing_quantity);
    }

    public function test_purchase_request_creation_sends_email_to_active_purchase_recipients(): void
    {
        Permission::findOrCreate('supplies.admin', 'web');
        Permission::findOrCreate('supplies.request', 'web');
        Role::findOrCreate('PROVEEDURIA_ADMIN', 'web')->syncPermissions(['supplies.admin', 'supplies.request']);

        Mail::fake();
        $emailOne = 'compras1+' . Str::lower(Str::random(8)) . '@example.com';
        $emailTwo = 'compras2+' . Str::lower(Str::random(8)) . '@example.com';
        $emailInactive = 'inactivo+' . Str::lower(Str::random(8)) . '@example.com';
        SupplyPurchaseRecipient::query()->delete();

        $user = User::factory()->create([
            'email' => 'supplies-mail@example.com',
        ]);
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        SupplyPurchaseRecipient::query()->create([
            'name' => 'Compras principal',
            'email' => $emailOne,
            'is_active' => true,
        ]);

        SupplyPurchaseRecipient::query()->create([
            'name' => 'Compras respaldo',
            'email' => $emailTwo,
            'is_active' => true,
        ]);

        SupplyPurchaseRecipient::query()->create([
            'name' => 'Compras inactivo',
            'email' => $emailInactive,
            'is_active' => false,
        ]);

        $client = SupplyClient::query()->first();
        $product = SupplyProduct::query()->firstOrFail();
        $supplyRequest = SupplyRequest::query()->create([
            'request_number' => 'SOL-TEST-' . Str::upper(Str::random(6)),
            'requested_by_user_id' => $user->id,
            'supply_client_id' => $client?->id,
            'status' => SupplyRequest::STATUS_REQUESTED,
            'request_notes' => 'Pedido para compras',
            'requested_at' => now(),
        ]);

        SupplyRequestItem::query()->create([
            'supply_request_id' => $supplyRequest->id,
            'supply_product_id' => $product->id,
            'requested_quantity' => 3,
            'received_quantity' => 0,
            'missing_quantity' => 0,
        ]);

        $sent = app(SupplyPurchaseNotificationService::class)->sendRequestCreatedNotification($supplyRequest);

        $this->assertSame(2, $sent);
        Mail::assertSent(SupplyPurchaseRequestCreated::class);
    }
}

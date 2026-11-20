<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\LegacyWarehousePermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReadOnlyInventoryAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_consultation_role_can_view_dashboard_but_cannot_modify_inventory(): void
    {
        $role = Role::findOrCreate('READ_ONLY_INVENTORY_TEST', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('dashboard.view', 'web'),
            Permission::findOrCreate('inventories.view', 'web'),
            Permission::findOrCreate('inventory.outputs.view', 'web'),
        ]);
        $customer = Customer::create([
            'name' => 'Cliente Consulta Inventario',
            'email' => 'read-only-inventory@example.com',
            'phone' => '3000000013',
            'address' => 'Calle 13',
        ]);
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $user->syncRoles([$role]);
        $user->customerAccesses()->attach($customer->customer_id);

        $this->assertSame([], app(LegacyWarehousePermissionService::class)->forUser($user));

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('selectedCustomer', $customer->name);

        $this->postJson(route('inventories.store'), [
            'customer' => $customer->name,
        ])->assertForbidden();
    }
}

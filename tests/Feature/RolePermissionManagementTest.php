<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_assign_operational_permissions_without_removing_legacy_permissions(): void
    {
        $superAdminRole = Role::findOrCreate('SUPERADMIN', 'web');
        $managedPermission = Permission::findOrCreate('dashboard.view', 'web');
        $legacyPermission = Permission::findOrCreate('legacy.matrix.scope', 'web');
        $targetRole = Role::findOrCreate('MATRIX_ROLE_TEST', 'web');
        $targetRole->syncPermissions([$legacyPermission]);

        $superAdmin = User::factory()->create();
        $superAdmin->syncRoles([$superAdminRole]);

        $this->actingAs($superAdmin)->withSession(['two_factor_verified' => true])
            ->get(route('role_permissions.index', ['role' => $targetRole->id]))
            ->assertOk()
            ->assertSee('Permisos por rol')
            ->assertSee('Ver panel principal');

        $this->post(route('role_permissions.assign', $targetRole->id), [
            'permissions' => [$managedPermission->id],
        ])->assertRedirect(route('role_permissions.index', ['role' => $targetRole->id]));

        $targetRole->refresh();
        $this->assertTrue($targetRole->hasPermissionTo($managedPermission));
        $this->assertTrue($targetRole->hasPermissionTo($legacyPermission));
    }

    public function test_view_permission_does_not_grant_location_management(): void
    {
        $viewerRole = Role::findOrCreate('LOCATION_VIEWER_TEST', 'web');
        $viewerRole->syncPermissions([Permission::findOrCreate('locations.view', 'web')]);

        $customer = Customer::create([
            'name' => 'Cliente Permiso Ubicacion',
            'email' => 'location-permission@example.com',
            'phone' => '3000000000',
            'address' => 'Calle 1',
        ]);
        $user = User::factory()->create();
        $user->syncRoles([$viewerRole]);
        $user->customerAccesses()->attach($customer->customer_id);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->postJson(route('locations.store'), [
            'code' => 'A-1',
            'name' => 'Ubicacion de prueba',
            'customer' => $customer->name,
            'max_capacity' => 10,
        ])->assertForbidden();
    }
}

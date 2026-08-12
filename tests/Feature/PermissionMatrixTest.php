<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\WarehouseGuide;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function assignCustomerAccess(User $user, Customer $customer): void
    {
        $user->customerAccesses()->syncWithoutDetaching([$customer->customer_id]);
    }

    private function grantCustomerPermission(Role $role, Customer $customer): void
    {
        // Customer scope is assigned per user. Kept for old test setup readability.
    }

    public function test_super_admin_can_access_warehouse_and_admin_routes(): void
    {
        $superAdminRole = Role::findOrCreate('SUPERADMIN', 'web');
        Permission::findOrCreate('password.create', 'web');
        Permission::findOrCreate('warehouse.view', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Base',
            'email' => 'cliente-base@example.com',
            'phone' => '3001112222',
            'address' => 'Av. Principal 100',
        ]);

        $user = User::factory()->create([
            'name' => 'Test Super Admin',
            'email' => 'superadmin-test@example.com',
        ]);
        $user->syncRoles([$superAdminRole]);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
        ]);

        $this->get(route('warehouse.index'))->assertOk();
        $this->get(route('roles.index'))->assertOk();
    }

    public function test_warehouse_only_user_can_access_warehouse_but_not_admin_views(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');
        Permission::findOrCreate('warehouse.export', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Bodega',
            'email' => 'cliente-bodega@example.com',
            'phone' => '3005556666',
            'address' => 'Carrera 1 # 2-3',
            'is_warehouse_client' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Test Warehouse',
            'email' => 'warehouse-test@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
        ]);

        $this->get(route('warehouse.index'))->assertOk();
        $this->get(route('inventories.index'))->assertRedirect(route('warehouse.index'));
        $this->get(route('roles.index'))->assertForbidden();
    }

    public function test_warehouse_only_user_is_blocked_from_admin_management_routes(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');
        Permission::findOrCreate('warehouse.export', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Bodega Admin',
            'email' => 'cliente-bodega-admin@example.com',
            'phone' => '3007778888',
            'address' => 'Carrera 9 # 9-9',
            'is_warehouse_client' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Warehouse Admin Blocked',
            'email' => 'warehouse-admin-blocked@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
        ]);

        $this->get(route('admin.index'))->assertForbidden();
        $this->get(route('permissions.index'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('profiles.index'))->assertForbidden();
        $this->get(route('role_permissions.index'))->assertForbidden();
    }

    public function test_super_admin_can_access_admin_management_routes_without_selected_customer(): void
    {
        $superAdminRole = Role::findOrCreate('SUPERADMIN', 'web');

        $user = User::factory()->create([
            'name' => 'Super Admin Context Free',
            'email' => 'superadmin-context-free@example.com',
        ]);
        $user->syncRoles([$superAdminRole]);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
        ]);

        $this->get(route('admin.index'))->assertOk();
        $this->get(route('permissions.index'))->assertOk();
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('profiles.index'))->assertOk();
        $this->get(route('role_permissions.index'))->assertOk();
    }

    public function test_warehouse_only_user_without_customer_is_redirected_to_context(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');
        Permission::findOrCreate('warehouse.export', 'web');

        $user = User::factory()->create([
            'name' => 'Test Warehouse No Customer',
            'email' => 'warehouse-nocustomer@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);

        $this->actingAs($user)->withSession(['two_factor_verified' => true]);

        $this->get(route('warehouse.index'))->assertRedirect(route('customer.context.index'));
        $this->get(route('dashboard'))->assertRedirect(route('customer.context.index'));
    }

    public function test_warehouse_context_only_shows_customer_permissions_assigned_to_role(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $allowedCustomer = Customer::create([
            'name' => 'CARGOSMART PERMITIDO TEST',
            'email' => 'cargosmart-permitido@example.com',
            'phone' => '3005550001',
            'address' => 'Calle 501',
            'is_warehouse_client' => true,
        ]);

        $deniedCustomer = Customer::create([
            'name' => 'EASY GO SIN PERMISO TEST',
            'email' => 'easy-go-sin-permiso@example.com',
            'phone' => '3005550002',
            'address' => 'Calle 502',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Context Permission',
            'email' => 'warehouse-context-permission@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $allowedCustomer);

        $this->actingAs($user)->withSession(['two_factor_verified' => true])
            ->get(route('customer.context.index'))
            ->assertOk()
            ->assertSee($allowedCustomer->name)
            ->assertDontSee($deniedCustomer->name);

        $this->post(route('customer.context.store'), [
            'customer' => $deniedCustomer->name,
        ])->assertSessionHasErrors('customers');
    }

    public function test_customer_access_can_be_limited_per_user_even_with_same_role(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $cargoSmart = Customer::create([
            'name' => 'CARGOSMART USER SCOPE TEST',
            'email' => 'cargosmart-user-scope@example.com',
            'phone' => '3005550010',
            'address' => 'Calle 510',
            'is_warehouse_client' => true,
        ]);

        $easyGo = Customer::create([
            'name' => 'EASY GO USER SCOPE TEST',
            'email' => 'easy-go-user-scope@example.com',
            'phone' => '3005550011',
            'address' => 'Calle 511',
            'is_warehouse_client' => true,
        ]);

        $cargoUser = User::factory()->create([
            'name' => 'Cargo User Scope',
            'email' => 'cargo-user-scope@example.com',
        ]);
        $cargoUser->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($cargoUser, $cargoSmart);

        $easyUser = User::factory()->create([
            'name' => 'Easy User Scope',
            'email' => 'easy-user-scope@example.com',
        ]);
        $easyUser->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($easyUser, $easyGo);

        $this->actingAs($cargoUser)->withSession(['two_factor_verified' => true])
            ->get(route('customer.context.index'))
            ->assertOk()
            ->assertSee($cargoSmart->name)
            ->assertDontSee($easyGo->name);

        $this->actingAs($easyUser)->withSession(['two_factor_verified' => true])
            ->get(route('customer.context.index'))
            ->assertOk()
            ->assertSee($easyGo->name)
            ->assertDontSee($cargoSmart->name);
    }

    public function test_warehouse_user_with_one_allowed_customer_is_auto_selected_after_2fa(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'CARGOSMART AUTOSELECT TEST',
            'email' => 'cargosmart-autoselect@example.com',
            'phone' => '3005550003',
            'address' => 'Calle 503',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Auto Select',
            'email' => 'warehouse-autoselect@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $this->actingAs($user)->withSession([
            'pending_2fa' => true,
            'two_factor_code' => '123456',
            'two_factor_code_expires' => now()->addMinutes(5),
        ])->post(route('two-factor.verify-code'), [
            'code' => '123456',
        ])->assertRedirect(route('warehouse.index'));

        $this->assertSame($customer->name, session('selected_customer'));
        $this->assertSame([$customer->name], session('selected_customers'));
    }

    public function test_login_skips_two_factor_when_disabled_from_env(): void
    {
        Config::set('auth.two_factor_enabled', false);

        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'CARGOSMART LOGIN WITHOUT 2FA TEST',
            'email' => 'cargosmart-login-without-2fa@example.com',
            'phone' => '3005550900',
            'address' => 'Calle 590',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Without Two Factor',
            'email' => 'warehouse-without-two-factor@example.com',
            'password' => bcrypt('secret123'),
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $this->post(route('login.action'), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect(route('warehouse.index'));

        $this->assertTrue((bool) session('two_factor_verified'));
        $this->assertFalse(session()->has('pending_2fa'));
        $this->assertSame($customer->name, session('selected_customer'));
    }

    public function test_authenticated_user_is_not_blocked_by_two_factor_middleware_when_disabled(): void
    {
        Config::set('auth.two_factor_enabled', false);

        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');
        Permission::findOrCreate('warehouse.export', 'web');

        $customer = Customer::create([
            'name' => 'CARGOSMART DISABLED MIDDLEWARE TEST',
            'email' => 'cargosmart-disabled-middleware@example.com',
            'phone' => '3005550901',
            'address' => 'Calle 591',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Disabled Middleware',
            'email' => 'warehouse-disabled-middleware@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $this->actingAs($user)->withSession([
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
            'two_factor_verified' => false,
            'pending_2fa' => true,
        ])->get(route('warehouse.index'))
            ->assertOk();
    }

    public function test_locations_data_redirects_warehouse_customer_selection_to_context(): void
    {
        $superAdminRole = Role::findOrCreate('SUPERADMIN', 'web');

        $warehouseCustomer = Customer::create([
            'name' => 'EASY GO LOCATIONS ISOLATION TEST',
            'email' => 'easy-go-locations-isolation@example.com',
            'phone' => '3005550020',
            'address' => 'Calle 520',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Locations Isolation User',
            'email' => 'locations-isolation-user@example.com',
        ]);
        $user->syncRoles([$superAdminRole]);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $warehouseCustomer->name,
            'selected_customers' => [$warehouseCustomer->name],
        ])->getJson(route('locations.data', ['warehouse' => 'BOGOTA']))
            ->assertRedirect(route('customer.context.index'));
    }

    public function test_mixed_user_uses_normal_customers_for_app_and_warehouse_customers_for_warehouse(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        $normalRole = Role::findOrCreate('USUARIO', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $normalCustomer = Customer::create([
            'name' => 'SKYONE MIXED CONTEXT TEST',
            'email' => 'skyone-mixed-context@example.com',
            'phone' => '3005550030',
            'address' => 'Calle 530',
            'is_warehouse_client' => false,
        ]);

        $warehouseCustomer = Customer::create([
            'name' => 'CARGOSMART MIXED CONTEXT TEST',
            'email' => 'cargosmart-mixed-context@example.com',
            'phone' => '3005550031',
            'address' => 'Calle 531',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Mixed Context User',
            'email' => 'mixed-context-user@example.com',
        ]);
        $user->syncRoles([$warehouseRole, $normalRole]);
        $user->customerAccesses()->sync([$normalCustomer->customer_id, $warehouseCustomer->customer_id]);

        $this->actingAs($user)->withSession(['two_factor_verified' => true])
            ->get(route('customer.context.index'))
            ->assertOk()
            ->assertSee($normalCustomer->name)
            ->assertDontSee($warehouseCustomer->name);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $normalCustomer->name,
            'selected_customers' => [$normalCustomer->name],
        ])->get(route('warehouse.index'))
            ->assertOk()
            ->assertSee($warehouseCustomer->name)
            ->assertDontSee('Cliente: ' . $normalCustomer->name);
    }

    public function test_normal_views_reject_warehouse_customer_selection(): void
    {
        $normalRole = Role::findOrCreate('USUARIO', 'web');

        $normalCustomer = Customer::create([
            'name' => 'SKYONE NORMAL ROUTE TEST',
            'email' => 'skyone-normal-route@example.com',
            'phone' => '3005550040',
            'address' => 'Calle 540',
            'is_warehouse_client' => false,
        ]);

        $warehouseCustomer = Customer::create([
            'name' => 'EASY GO NORMAL ROUTE TEST',
            'email' => 'easy-go-normal-route@example.com',
            'phone' => '3005550041',
            'address' => 'Calle 541',
            'is_warehouse_client' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Normal Route User',
            'email' => 'normal-route-user@example.com',
        ]);
        $user->syncRoles([$normalRole]);
        $user->customerAccesses()->sync([$normalCustomer->customer_id, $warehouseCustomer->customer_id]);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $warehouseCustomer->name,
            'selected_customers' => [$warehouseCustomer->name],
        ])->get(route('inventories.index'))
            ->assertRedirect(route('customer.context.index'));

        $this->assertNull(session('selected_customer'));
        $this->assertNull(session('selected_customers'));
    }

    public function test_admin_update_syncs_role_names_from_form_submission(): void
    {
        $superAdminRole = Role::findOrCreate('SUPERADMIN', 'web');
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('password.create', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');
        Permission::findOrCreate('warehouse.export', 'web');

        $actor = User::factory()->create([
            'name' => 'Role Manager',
            'email' => 'role-manager@example.com',
        ]);
        $actor->syncRoles([$superAdminRole]);

        $customer = Customer::create([
            'name' => 'Cliente Admin',
            'email' => 'cliente-admin@example.com',
            'phone' => '3003334444',
            'address' => 'Calle 456',
        ]);

        $target = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target-user@example.com',
        ]);

        $this->actingAs($actor)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
        ]);

        $response = $this->put(route('admin.update', $target->id), [
            'roles' => [$warehouseRole->name],
        ]);

        $response->assertRedirect(route('admin.edit', $target->id));

        $target->refresh();
        $this->assertTrue($target->hasRole('BODEGA'));
        $this->assertFalse($target->hasRole('SUPERADMIN'));
    }

    public function test_verified_user_is_redirected_to_customer_context_before_entering_app_without_selection(): void
    {
        $superAdminRole = Role::findOrCreate('SUPERADMIN', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Prueba',
            'email' => 'cliente-prueba@example.com',
            'phone' => '3000000000',
            'address' => 'Calle 123',
        ]);

        $user = User::factory()->create([
            'name' => 'Context User',
            'email' => 'context-user@example.com',
        ]);
        $user->syncRoles([$superAdminRole]);

        $this->actingAs($user)->withSession(['two_factor_verified' => true]);

        $this->get(route('dashboard'))->assertRedirect(route('customer.context.index'));
        $this->get(route('inventories.index'))->assertRedirect(route('customer.context.index'));

        $response = $this->withSession([
            'two_factor_verified' => true,
            'url.intended' => route('dashboard'),
        ])->post(route('customer.context.store'), [
            'customer' => $customer->name,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame($customer->name, session('selected_customer'));
    }

    public function test_warehouse_user_can_import_guide_entries_from_csv(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Importacion Ingresos',
            'email' => 'cliente-importacion-ingresos@example.com',
            'phone' => '3001230000',
            'address' => 'Calle 101',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $location = WarehouseLocation::create([
            'code' => 'ALMACENAMIENTO',
            'customer' => $customer->name,
            'name' => 'Almacenamiento',
            'warehouse' => $customer->name,
            'description' => 'Zona transitoria',
            'is_active' => true,
            'is_storage' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Import Entries',
            'email' => 'warehouse-import-entries@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $file = UploadedFile::fake()->createWithContent(
            'ingresos.csv',
            "GUIA,LOCALIZACION\nGL000024273CO,ALMACENAMIENTO\n"
        );

        $response = $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->post(route('warehouse.import.entries'), [
            'customer' => $customer->name,
            'warehouse' => $customer->name,
            'file' => $file,
        ]);

        $response->assertRedirect(route('warehouse.index', ['tab' => 'guides']));
        $this->assertDatabaseHas('warehouse_guides', [
            'customer' => $customer->name,
            'guide' => 'GL000024273CO',
            'current_location_id' => $location->location_id,
            'current_location_code' => 'ALMACENAMIENTO',
            'status' => WarehouseGuide::STATUS_ACTIVE,
        ]);
    }

    public function test_warehouse_user_can_import_guide_exits_from_csv(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Importacion Salidas',
            'email' => 'cliente-importacion-salidas@example.com',
            'phone' => '3001239999',
            'address' => 'Calle 202',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $location = WarehouseLocation::create([
            'code' => 'A1',
            'customer' => $customer->name,
            'name' => 'Pasillo A1',
            'warehouse' => $customer->name,
            'description' => 'Rack principal',
            'is_active' => true,
            'is_storage' => false,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Import Exits',
            'email' => 'warehouse-import-exits@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $guide = WarehouseGuide::create([
            'guide' => 'GL000024274CO',
            'customer' => $customer->name,
            'warehouse' => $location->warehouse,
            'status' => WarehouseGuide::STATUS_ACTIVE,
            'entry_at' => now()->subHour(),
            'entry_source' => 'manual',
            'entry_user_id' => $user->id,
            'current_location_id' => $location->location_id,
            'current_location_code' => $location->code,
            'current_location_name' => $location->name,
            'notes' => 'Ingreso previo',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'salidas.csv',
            "GUIA,GUIA_NACIONAL\nGL000024274CO,GN000000001CO\n"
        );

        $response = $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->post(route('warehouse.import.exits'), [
            'customer' => $customer->name,
            'warehouse' => $customer->name,
            'file' => $file,
        ]);

        $response->assertRedirect(route('warehouse.index', ['tab' => 'guides']));

        $guide->refresh();
        $this->assertNotNull($guide->exit_at);
        $this->assertSame(WarehouseGuide::STATUS_EXITED, $guide->status);
        $this->assertSame('GN000000001CO', $guide->national_guide);
    }

    public function test_warehouse_exit_import_preserves_original_entry_time(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Importacion Conserva Hora',
            'email' => 'cliente-importacion-conserva-hora@example.com',
            'phone' => '3001249999',
            'address' => 'Calle 216',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $location = WarehouseLocation::create([
            'code' => 'ALMACENAMIENTO',
            'customer' => $customer->name,
            'name' => 'Almacenamiento',
            'warehouse' => $customer->name,
            'description' => 'Zona conserva hora',
            'is_active' => true,
            'is_storage' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Import Preserve Time',
            'email' => 'warehouse-import-preserve-time@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $entryAt = Carbon::create(2026, 5, 13, 16, 10, 0, 'America/Bogota');
        $guide = WarehouseGuide::create([
            'guide' => 'GL000025101CO',
            'customer' => $customer->name,
            'warehouse' => $customer->name,
            'status' => WarehouseGuide::STATUS_ACTIVE,
            'entry_at' => $entryAt,
            'entry_source' => 'manual',
            'entry_user_id' => $user->id,
            'current_location_id' => $location->location_id,
            'current_location_code' => $location->code,
            'current_location_name' => $location->name,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'salidas.csv',
            "GUIA,GUIA_NACIONAL\nGL000025101CO,GN000000101CO\n"
        );

        try {
            Carbon::setTestNow(Carbon::create(2026, 5, 13, 16, 36, 0, 'America/Bogota'));

            $this->actingAs($user)->withSession([
                'two_factor_verified' => true,
                'selected_customer' => $customer->name,
                'selected_customers' => [$customer->name],
            ])->post(route('warehouse.import.exits'), [
                'customer' => $customer->name,
                'warehouse' => $customer->name,
                'file' => $file,
            ])->assertRedirect(route('warehouse.index', ['tab' => 'guides']));
        } finally {
            Carbon::setTestNow();
        }

        $guide->refresh();
        $this->assertSame('2026-05-13 16:10:00', $guide->entry_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-13 16:36:00', $guide->exit_at->format('Y-m-d H:i:s'));
        $this->assertSame(26, $guide->duration_minutes);
    }

    public function test_warehouse_entry_exit_and_duration_use_bogota_time(): void
    {
        $this->assertSame('America/Bogota', config('app.timezone'));

        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Tiempo Bogota',
            'email' => 'cliente-tiempo-bogota@example.com',
            'phone' => '3001240000',
            'address' => 'Calle 215',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $location = WarehouseLocation::create([
            'code' => 'A1',
            'customer' => $customer->name,
            'name' => 'Rack A1',
            'warehouse' => $customer->name,
            'description' => 'Zona tiempo',
            'is_active' => true,
            'is_storage' => false,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Time User',
            'email' => 'warehouse-time-user@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        try {
            Carbon::setTestNow(Carbon::create(2026, 5, 13, 16, 15, 0, 'America/Bogota'));

            $entryResponse = $this->actingAs($user)->withSession([
                'two_factor_verified' => true,
                'selected_customer' => $customer->name,
                'selected_customers' => [$customer->name],
            ])->postJson(route('warehouse.guides.store'), [
                'customer' => $customer->name,
                'guide' => 'GL000025100CO',
                'location_id' => $location->location_id,
            ]);

            $entryResponse->assertCreated()
                ->assertJsonPath('guide.guide.entry_at', '13/05/2026 16:15');

            Carbon::setTestNow(Carbon::create(2026, 5, 13, 16, 40, 0, 'America/Bogota'));

            $exitResponse = $this->actingAs($user)->withSession([
                'two_factor_verified' => true,
                'selected_customer' => $customer->name,
                'selected_customers' => [$customer->name],
            ])->postJson(route('warehouse.guides.exit'), [
                'guide' => 'GL000025100CO',
                'national_guide' => 'GN000000100CO',
            ]);

            $exitResponse->assertOk()
                ->assertJsonPath('guide.guide.exit_at', '13/05/2026 16:40')
                ->assertJsonPath('guide.guide.duration_label', '25m');
        } finally {
            Carbon::setTestNow();
        }

        $guide = WarehouseGuide::where('customer', $customer->name)
            ->where('guide', 'GL000025100CO')
            ->firstOrFail();

        $this->assertSame('2026-05-13 16:15:00', $guide->entry_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-13 16:40:00', $guide->exit_at->format('Y-m-d H:i:s'));
        $this->assertSame(25, $guide->duration_minutes);
    }

    public function test_warehouse_user_can_register_grouped_exit_with_national_guide(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'Cliente Salida Agrupada',
            'email' => 'cliente-salida-agrupada@example.com',
            'phone' => '3008881111',
            'address' => 'Calle 303',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $location = WarehouseLocation::create([
            'code' => 'B2',
            'customer' => $customer->name,
            'name' => 'Pasillo B2',
            'warehouse' => $customer->name,
            'description' => 'Zona de despacho',
            'is_active' => true,
            'is_storage' => false,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Group Exit',
            'email' => 'warehouse-group-exit@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $guideA = WarehouseGuide::create([
            'guide' => 'GL000024280CO',
            'customer' => $customer->name,
            'warehouse' => $location->warehouse,
            'status' => WarehouseGuide::STATUS_ACTIVE,
            'entry_at' => now()->subHours(3),
            'entry_source' => 'manual',
            'entry_user_id' => $user->id,
            'current_location_id' => $location->location_id,
            'current_location_code' => $location->code,
            'current_location_name' => $location->name,
        ]);

        $guideB = WarehouseGuide::create([
            'guide' => 'GL000024281CO',
            'customer' => $customer->name,
            'warehouse' => $location->warehouse,
            'status' => WarehouseGuide::STATUS_ACTIVE,
            'entry_at' => now()->subHours(2),
            'entry_source' => 'manual',
            'entry_user_id' => $user->id,
            'current_location_id' => $location->location_id,
            'current_location_code' => $location->code,
            'current_location_name' => $location->name,
        ]);

        $response = $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->postJson(route('warehouse.guides.exit-grouped'), [
            'guides' => [$guideA->guide, $guideB->guide],
            'national_guide' => 'GN000000777CO',
            'notes' => 'Salida consolidada',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'processed' => 2,
            'national_guide' => 'GN000000777CO',
        ]);

        $guideA->refresh();
        $guideB->refresh();

        $this->assertSame(WarehouseGuide::STATUS_EXITED, $guideA->status);
        $this->assertSame(WarehouseGuide::STATUS_EXITED, $guideB->status);
        $this->assertSame('GN000000777CO', $guideA->national_guide);
        $this->assertSame('GN000000777CO', $guideB->national_guide);
    }

    public function test_warehouse_locations_are_scoped_by_customer_and_warehouse(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $cargoSmart = Customer::create([
            'name' => 'CARGOESMART',
            'email' => 'cargoesmart@example.com',
            'phone' => '3001234567',
            'address' => 'Calle 303',
            'is_warehouse_client' => true,
        ]);

        $easyGo = Customer::create([
            'name' => 'EASY GO CARGO LOCATION TEST',
            'email' => 'easy-go-cargo@example.com',
            'phone' => '3001234568',
            'address' => 'Calle 304',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $cargoSmart);
        $this->grantCustomerPermission($warehouseRole, $easyGo);

        $user = User::factory()->create([
            'name' => 'Warehouse Multi Location',
            'email' => 'warehouse-multi-location@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $cargoSmart);
        $this->assignCustomerAccess($user, $easyGo);

        $session = [
            'two_factor_verified' => true,
            'selected_customer' => $cargoSmart->name,
            'selected_customers' => [$cargoSmart->name, $easyGo->name],
        ];

        $this->actingAs($user)->withSession($session)->postJson(route('warehouse.locations.store'), [
            'code' => 'A1',
            'name' => 'Rack CargoSmart',
            'customer' => $cargoSmart->name,
            'warehouse' => $cargoSmart->name,
            'description' => 'Ubicacion CargoSmart',
            'is_active' => true,
            'is_storage' => false,
        ])->assertCreated();

        $this->actingAs($user)->withSession($session)->postJson(route('warehouse.locations.store'), [
            'code' => 'A1',
            'name' => 'Rack Easy Go',
            'customer' => $easyGo->name,
            'warehouse' => $easyGo->name,
            'description' => 'Ubicacion Easy Go',
            'is_active' => true,
            'is_storage' => false,
        ])->assertCreated();

        $this->assertDatabaseHas('warehouse_locations', [
            'customer' => $cargoSmart->name,
            'warehouse' => $cargoSmart->name,
            'code' => 'A1',
        ]);

        $this->assertDatabaseHas('warehouse_locations', [
            'customer' => $easyGo->name,
            'warehouse' => $easyGo->name,
            'code' => 'A1',
        ]);
    }

    public function test_warehouse_entry_import_uses_selected_customer_and_warehouse(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'EASY GO CARGO IMPORT SCOPE TEST',
            'email' => 'cliente-importacion-bodega-separada@example.com',
            'phone' => '3004567890',
            'address' => 'Calle 404',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $easyGoLocation = WarehouseLocation::create([
            'code' => 'A1',
            'customer' => $customer->name,
            'name' => 'A1 Easy Go',
            'warehouse' => $customer->name,
            'description' => 'Zona Easy Go',
            'is_active' => true,
            'is_storage' => false,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Import Warehouse Scope',
            'email' => 'warehouse-import-warehouse-scope@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $file = UploadedFile::fake()->createWithContent(
            'ingresos.csv',
            "GUIA,LOCALIZACION\nGL000024999CO,A1\n"
        );

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->post(route('warehouse.import.entries'), [
            'customer' => $customer->name,
            'warehouse' => $customer->name,
            'file' => $file,
        ])->assertRedirect(route('warehouse.index', ['tab' => 'guides']));

        $this->assertDatabaseHas('warehouse_guides', [
            'customer' => $customer->name,
            'warehouse' => $customer->name,
            'guide' => 'GL000024999CO',
            'current_location_id' => $easyGoLocation->location_id,
        ]);
    }

    public function test_warehouse_entry_import_accepts_customer_case_differences(): void
    {
        $warehouseRole = Role::findOrCreate('BODEGA', 'web');
        Permission::findOrCreate('warehouse.view', 'web');
        Permission::findOrCreate('warehouse.manage', 'web');

        $customer = Customer::create([
            'name' => 'Easy go cargo case test',
            'email' => 'easy-go-case@example.com',
            'phone' => '3004567891',
            'address' => 'Calle 405',
            'is_warehouse_client' => true,
        ]);
        $this->grantCustomerPermission($warehouseRole, $customer);

        $location = WarehouseLocation::create([
            'code' => 'ALMACENAMIENTO',
            'customer' => $customer->name,
            'name' => 'Almacenamiento Easy Go',
            'warehouse' => $customer->name,
            'description' => 'Zona Easy Go',
            'is_active' => true,
            'is_storage' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Warehouse Import Case',
            'email' => 'warehouse-import-case@example.com',
        ]);
        $user->syncRoles([$warehouseRole]);
        $this->assignCustomerAccess($user, $customer);

        $file = UploadedFile::fake()->createWithContent(
            'ingresos.csv',
            "CLIENTE,GUIA,LOCALIZACION\nEASY GO CARGO CASE TEST,GL000024274CO,ALMACENAMIENTO\n"
        );

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->post(route('warehouse.import.entries'), [
            'customer' => $customer->name,
            'file' => $file,
        ])->assertRedirect(route('warehouse.index', ['tab' => 'guides']));

        $this->assertDatabaseHas('warehouse_guides', [
            'customer' => $customer->name,
            'warehouse' => $customer->name,
            'guide' => 'GL000024274CO',
            'current_location_id' => $location->location_id,
        ]);
    }
}

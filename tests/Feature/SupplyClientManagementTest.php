<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyClientManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('auth.two_factor_enabled', false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('supplies.admin', 'web');
        Permission::findOrCreate('supplies.request', 'web');
        Role::findOrCreate('PROVEEDURIA_ADMIN', 'web')->syncPermissions(['supplies.admin', 'supplies.request']);
    }

    public function test_supply_client_can_be_created_with_name_address_and_city(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        $this->actingAs($user)
            ->post(route('supplies.clients.store'), [
                'name' => 'CLIENTE PRUEBA',
                'address' => 'Carrera 12 # 34-56',
                'city' => 'BOGOTA',
                'is_active' => 1,
            ])
            ->assertRedirect(route('supplies.index', ['tab' => 'clients']));

        $this->assertDatabaseHas('supply_clients', [
            'name' => 'CLIENTE PRUEBA',
            'address' => 'Carrera 12 # 34-56',
            'city' => 'BOGOTA',
        ]);
    }

    public function test_supply_clients_can_be_imported_from_csv(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        $csv = implode("\n", [
            'NOMBRE,DIRECCIÓN,CIUDAD',
            '"DERCO","Calle 80 # 11-20","BOGOTA"',
            '"SKYONE","Carrera 44 # 21-10","MEDELLIN"',
        ]);

        $file = UploadedFile::fake()->createWithContent('clientes_proveeduria.csv', $csv);

        $this->actingAs($user)
            ->post(route('supplies.clients.import'), [
                'file' => $file,
            ])
            ->assertRedirect(route('supplies.index', ['tab' => 'clients']));

        $this->assertDatabaseHas('supply_clients', [
            'name' => 'DERCO',
            'address' => 'Calle 80 # 11-20',
            'city' => 'BOGOTA',
        ]);

        $this->assertDatabaseHas('supply_clients', [
            'name' => 'SKYONE',
            'address' => 'Carrera 44 # 21-10',
            'city' => 'MEDELLIN',
        ]);
    }

    public function test_supply_client_template_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        $this->actingAs($user)
            ->get(route('supplies.clients.template'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=plantilla_clientes_proveeduria.xlsx');
    }
}

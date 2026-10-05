<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HrEmployeeSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HrEmployeeSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.hr_employees.url', 'https://rh.example.test/api/employees');
        Config::set('services.hr_employees.token', 'test-token');
    }

    public function test_sync_creates_an_inactive_user_when_hr_reports_inactive_status(): void
    {
        Http::fake([
            'https://rh.example.test/api/employees' => Http::response([
                'data' => [[
                    'id' => 'hr-inactive-001',
                    'names' => 'Empleado Inactivo RH',
                    'corporate_email' => 'empleado.inactivo.rh@example.com',
                    'personal_email' => 'respaldo.inactivo@example.com',
                    'corporate_phone' => '3001112233',
                    'personal_phone' => '3009998877',
                    'address' => 'Calle 10 # 20-30',
                    'position' => 'Analista',
                    'process' => 'Operaciones',
                    'regional' => 'Bogota',
                    'status' => 'INACTIVO',
                ]],
            ]),
        ]);

        app(HrEmployeeSyncService::class)->sync();

        $this->assertDatabaseHas('users', [
            'hr_employee_id' => 'hr-inactive-001',
            'email' => 'empleado.inactivo.rh@example.com',
            'is_active' => false,
            'telephone' => '3001112233',
            'address' => 'Calle 10 # 20-30',
        ]);
    }

    public function test_sync_uses_personal_contact_data_when_corporate_contact_data_is_empty(): void
    {
        Http::fake([
            'https://rh.example.test/api/employees' => Http::response([
                'data' => [[
                    'id' => 'hr-contact-002',
                    'names' => 'Empleado Contacto RH',
                    'corporate_email' => null,
                    'personal_email' => 'empleado.contacto@example.com',
                    'corporate_phone' => null,
                    'personal_phone' => '3015556677',
                    'address' => 'Carrera 40 # 12-20',
                    'is_active' => 1,
                ]],
            ]),
        ]);

        app(HrEmployeeSyncService::class)->sync();

        $this->assertDatabaseHas('users', [
            'hr_employee_id' => 'hr-contact-002',
            'email' => 'empleado.contacto@example.com',
            'telephone' => '3015556677',
            'address' => 'Carrera 40 # 12-20',
            'is_active' => true,
        ]);
    }

    public function test_sync_updates_user_status_when_hr_reports_it(): void
    {
        $user = User::factory()->create([
            'hr_employee_id' => 'hr-status-002',
            'email' => 'estado.usuario@example.com',
            'is_active' => true,
        ]);

        Http::fake([
            'https://rh.example.test/api/employees' => Http::response([
                'data' => [[
                    'id' => 'hr-status-002',
                    'names' => 'Estado Usuario',
                    'corporate_email' => 'estado.usuario@example.com',
                    'is_active' => 2,
                ]],
            ]),
        ]);

        app(HrEmployeeSyncService::class)->sync();

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_sync_preserves_local_status_when_hr_does_not_send_a_status_field(): void
    {
        $user = User::factory()->create([
            'hr_employee_id' => 'hr-status-003',
            'email' => 'estado.local@example.com',
            'is_active' => false,
        ]);

        Http::fake([
            'https://rh.example.test/api/employees' => Http::response([
                'data' => [[
                    'id' => 'hr-status-003',
                    'names' => 'Estado Local',
                    'corporate_email' => 'estado.local@example.com',
                ]],
            ]),
        ]);

        app(HrEmployeeSyncService::class)->sync();

        $this->assertFalse($user->fresh()->is_active);
    }
}

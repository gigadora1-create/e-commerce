<?php

namespace Tests\Feature;

use App\Models\SupplyIssueRequest;
use App\Models\SupplyReqCaseSync;
use App\Models\SupplyClient;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyPhaseTwoTest extends TestCase
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
        Role::findOrCreate('PROVEEDURIA_USUARIO', 'web')->syncPermissions(['supplies.request']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_audit_receipt_increases_supply_stock(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $product = SupplyProduct::query()->firstOrFail();
        $product->update(['stock_on_hand' => 0, 'reserved_stock' => 0]);
        $this->actingAs($admin)->post(route('supplies.requests.store'), [
            'product_id' => [$product->id],
            'requested_quantity' => [8],
        ])->assertRedirect();

        $requestRecord = SupplyRequest::query()->latest('id')->firstOrFail();
        $item = $requestRecord->items()->firstOrFail();

        $this->actingAs($admin)->put(route('supplies.requests.audit', $requestRecord), [
            'received_by_name' => 'Recibe Admin',
            'delivered_by_name' => 'Entrega Compras',
            'received_quantity' => [
                $item->id => 6,
            ],
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame(6, (int) $product->stock_on_hand);
        $this->assertSame(0, (int) $product->reserved_stock);
    }

    public function test_partial_receipt_can_be_completed_in_a_later_audit(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $product = SupplyProduct::query()->firstOrFail();
        $product->update(['stock_on_hand' => 0, 'reserved_stock' => 0]);
        $this->actingAs($admin)->post(route('supplies.requests.store'), [
            'product_id' => [$product->id],
            'requested_quantity' => [10],
        ])->assertRedirect();

        $purchaseRequest = SupplyRequest::query()->latest('id')->firstOrFail();
        $item = $purchaseRequest->items()->firstOrFail();

        $receiptPayload = [
            'received_by_name' => 'Recibe Admin',
            'delivered_by_name' => 'Entrega Compras',
            'received_quantity' => [$item->id => 6],
        ];

        $this->actingAs($admin)->put(route('supplies.requests.audit', $purchaseRequest), $receiptPayload)
            ->assertRedirect();

        $purchaseRequest->refresh();
        $item->refresh();
        $this->assertSame(SupplyRequest::STATUS_PARTIAL, $purchaseRequest->status);
        $this->assertSame(6, (int) $item->received_quantity);
        $this->assertSame(4, (int) $item->missing_quantity);

        $receiptPayload['received_quantity'] = [$item->id => 4];
        $this->actingAs($admin)->put(route('supplies.requests.audit', $purchaseRequest), $receiptPayload)
            ->assertRedirect();

        $purchaseRequest->refresh();
        $item->refresh();
        $product->refresh();
        $this->assertSame(SupplyRequest::STATUS_COMPLETE, $purchaseRequest->status);
        $this->assertSame(10, (int) $item->received_quantity);
        $this->assertSame(0, (int) $item->missing_quantity);
        $this->assertSame(10, (int) $product->stock_on_hand);
    }

    public function test_issue_index_renders_client_picker_for_request_user(): void
    {
        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $this->actingAs($requester)
            ->get(route('supplies.issues.index'))
            ->assertOk()
            ->assertSee('Escriba para buscar cliente')
            ->assertSee('Nueva solicitud')
            ->assertSee('Mis solicitudes')
            ->assertSee('Listas para recoger')
            ->assertDontSee('Stock disponible')
            ->assertDontSee('Panel operativo de stock');
    }

    public function test_request_user_does_not_receive_stock_quantities_in_issue_catalog(): void
    {
        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $product->update(['stock_on_hand' => 12, 'reserved_stock' => 0]);

        $this->actingAs($requester)
            ->get(route('supplies.issues.index'))
            ->assertOk()
            ->assertDontSee('Disponible: 12')
            ->assertDontSee('"available":12');
    }

    public function test_live_availability_check_reports_unavailable_product_without_disclosing_stock(): void
    {
        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $product->update(['stock_on_hand' => 0, 'reserved_stock' => 0]);

        $this->actingAs($requester)
            ->postJson(route('supplies.issues.availability'), [
                'product_id' => [$product->id],
                'requested_quantity' => [1],
            ])
            ->assertOk()
            ->assertJson([
                'valid' => false,
                'errors' => [
                    (string) $product->id => 'No hay producto disponible para solicitar.',
                ],
            ])
            ->assertJsonMissing(['available_stock', 'stock_on_hand', 'reserved_stock']);
    }

    public function test_admin_issue_index_renders_operational_stock_panel(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $this->actingAs($admin)
            ->get(route('supplies.issues.index'))
            ->assertOk()
            ->assertSee('Panel operativo de stock')
            ->assertSee('Stock retenido')
            ->assertSee('Ult. movimiento')
            ->assertSee('Alerta de stock bajo');
    }

    public function test_issue_request_reserves_stock_and_close_deducts_it(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00', 'America/Bogota'));

        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();
        $product->update([
            'stock_on_hand' => 12,
            'reserved_stock' => 0,
        ]);

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [5],
            'request_notes' => 'Salida para oficina',
        ])->assertRedirect(route('supplies.issues.index'));

        $issueRequest = SupplyIssueRequest::query()->latest('id')->firstOrFail();
        $product->refresh();

        $this->assertSame(SupplyIssueRequest::STATUS_PREPARING, $issueRequest->status);
        $this->assertSame(12, (int) $product->stock_on_hand);
        $this->assertSame(5, (int) $product->reserved_stock);
        $this->assertSame(7, (int) $product->available_stock);

        $this->actingAs($admin)->put(route('supplies.issues.close', $issueRequest), [
            'support_received' => true,
        ])
            ->assertRedirect(route('supplies.issues.show', $issueRequest));

        $issueRequest->refresh();
        $product->refresh();

        $this->assertSame(SupplyIssueRequest::STATUS_CLOSED, $issueRequest->status);
        $this->assertSame(7, (int) $product->stock_on_hand);
        $this->assertSame(0, (int) $product->reserved_stock);
    }

    public function test_delivery_without_signed_support_stays_pending_until_confirmed(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00', 'America/Bogota'));

        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);
        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();
        $product->update(['stock_on_hand' => 8, 'reserved_stock' => 0]);

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [3],
        ])->assertRedirect();

        $issueRequest = SupplyIssueRequest::query()->latest('id')->firstOrFail();
        $item = $issueRequest->items()->firstOrFail();

        $this->actingAs($admin)->put(route('supplies.issues.close', $issueRequest), [
            'delivered_quantity' => [$item->id => 3],
        ])->assertRedirect(route('supplies.issues.show', $issueRequest));

        $issueRequest->refresh();
        $product->refresh();
        $this->assertSame(SupplyIssueRequest::STATUS_PENDING_SUPPORT, $issueRequest->status);
        $this->assertSame(5, (int) $product->stock_on_hand);
        $this->assertSame(0, (int) $product->reserved_stock);

        $this->actingAs($admin)->put(route('supplies.issues.confirm-support', $issueRequest))
            ->assertRedirect(route('supplies.issues.show', $issueRequest));

        $issueRequest->refresh();
        $product->refresh();
        $this->assertSame(SupplyIssueRequest::STATUS_CLOSED, $issueRequest->status);
        $this->assertSame(5, (int) $product->stock_on_hand);
    }

    public function test_admin_can_close_issue_request_with_partial_delivery(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00', 'America/Bogota'));

        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();
        $product->update(['stock_on_hand' => 12, 'reserved_stock' => 0]);

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [10],
        ])->assertRedirect();

        $issueRequest = SupplyIssueRequest::query()->latest('id')->firstOrFail();
        $item = $issueRequest->items()->firstOrFail();

        $this->actingAs($admin)->put(route('supplies.issues.close', $issueRequest), [
            'delivered_quantity' => [$item->id => 5],
            'admin_notes' => 'Entrega parcial autorizada',
            'support_received' => true,
        ])->assertRedirect(route('supplies.issues.show', $issueRequest));

        $issueRequest->refresh();
        $item->refresh();
        $product->refresh();

        $this->assertSame(SupplyIssueRequest::STATUS_CLOSED, $issueRequest->status);
        $this->assertSame(5, (int) $item->delivered_quantity);
        $this->assertSame(7, (int) $product->stock_on_hand);
        $this->assertSame(0, (int) $product->reserved_stock);
    }

    public function test_admin_can_reject_issue_request_and_release_reserved_stock(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00', 'America/Bogota'));

        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();
        $product->update([
            'stock_on_hand' => 15,
            'reserved_stock' => 0,
        ]);

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [4],
            'request_notes' => 'Solicitud para rechazo',
        ])->assertRedirect(route('supplies.issues.index'));

        $issueRequest = SupplyIssueRequest::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)->put(route('supplies.issues.reject', $issueRequest))
            ->assertRedirect(route('supplies.issues.show', $issueRequest));

        $issueRequest->refresh();
        $product->refresh();

        $this->assertSame(SupplyIssueRequest::STATUS_REJECTED, $issueRequest->status);
        $this->assertSame(15, (int) $product->stock_on_hand);
        $this->assertSame(0, (int) $product->reserved_stock);
    }

    public function test_request_user_only_sees_own_requests_and_pdf_after_close(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00', 'America/Bogota'));

        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $otherUser = User::factory()->create();
        $otherUser->syncRoles(['PROVEEDURIA_USUARIO']);

        $admin = User::factory()->create();
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();
        $product->update([
            'stock_on_hand' => 10,
            'reserved_stock' => 0,
        ]);

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [2],
        ])->assertRedirect();

        $ownRequest = SupplyIssueRequest::query()->latest('id')->firstOrFail();

        $this->actingAs($otherUser)->get(route('supplies.issues.show', $ownRequest))->assertForbidden();
        $this->actingAs($requester)->get(route('supplies.issues.pdf', $ownRequest))->assertForbidden();

        $this->actingAs($admin)->put(route('supplies.issues.close', $ownRequest), [
            'support_received' => true,
        ])->assertRedirect();

        $this->actingAs($requester)->get(route('supplies.issues.pdf', $ownRequest))->assertOk();
    }

    public function test_supply_requester_only_is_restricted_to_supply_issue_module(): void
    {
        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $this->actingAs($requester)
            ->withSession(['selected_customers' => ['CLIENTE TEST'], 'selected_customer' => 'CLIENTE TEST'])
            ->get(route('dashboard'))
            ->assertRedirect(route('supplies.issues.index'));

        $this->actingAs($requester)
            ->withSession(['selected_customers' => ['CLIENTE TEST'], 'selected_customer' => 'CLIENTE TEST'])
            ->get(route('inventories.index'))
            ->assertRedirect(route('supplies.issues.index'));
    }

    public function test_request_user_cannot_create_issue_request_on_wednesday_august_12_2026(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00', 'America/Bogota'));

        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [1],
            'request_notes' => 'Intento fuera de ventana',
        ])
            ->assertRedirect(route('supplies.issues.index'))
            ->assertSessionHas('error', 'Solo se puede enviar proveeduria los dias jueves y viernes.');
    }

    public function test_request_user_can_create_issue_request_on_thursday_august_13_2026(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00', 'America/Bogota'));

        $requester = User::factory()->create();
        $requester->syncRoles(['PROVEEDURIA_USUARIO']);

        $product = SupplyProduct::query()->firstOrFail();
        $client = SupplyClient::query()->firstOrFail();
        $product->update([
            'stock_on_hand' => 10,
            'reserved_stock' => 0,
        ]);

        $this->actingAs($requester)->post(route('supplies.issues.store'), [
            'supply_client_id' => $client->id,
            'product_id' => [$product->id],
            'requested_quantity' => [1],
            'request_notes' => 'Solicitud valida en jueves',
        ])->assertRedirect(route('supplies.issues.index'));

        $issueRequest = SupplyIssueRequest::query()->latest('id')->firstOrFail();

        $this->assertSame(SupplyIssueRequest::STATUS_PREPARING, $issueRequest->status);
        $this->assertSame((int) $requester->id, (int) $issueRequest->requested_by_user_id);
    }

    public function test_created_purchase_request_is_sent_to_req_and_stores_the_external_case_id(): void
    {
        Config::set('services.req_supply.url', 'https://req.example.test');
        Config::set('services.req_supply.token', 'integration-secret');

        Http::fake([
            'https://req.example.test/api/v1/proveeduria/cases' => Http::response([
                'data' => [
                    'case_id' => 9876,
                    'case_number' => '9876',
                    'status' => 'En proceso',
                ],
            ], 201),
        ]);

        $admin = User::factory()->create(['email' => 'requester-req@example.com']);
        $admin->syncRoles(['PROVEEDURIA_ADMIN']);
        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($admin)->post(route('supplies.requests.store'), [
            'product_id' => [$product->id],
            'requested_quantity' => [2],
            'request_notes' => 'Sincronizar con req',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Caso creado en req con ID 9876.'));

        $supplyRequest = SupplyRequest::query()->latest('id')->firstOrFail();
        $sync = SupplyReqCaseSync::query()
            ->where('supply_request_id', $supplyRequest->id)
            ->firstOrFail();

        $this->assertSame(SupplyReqCaseSync::STATUS_SYNCED, $sync->status);
        $this->assertSame(9876, (int) $sync->external_case_id);

        Http::assertSent(function ($request) use ($supplyRequest) {
            return $request->url() === 'https://req.example.test/api/v1/proveeduria/cases'
                && $request->hasHeader('Authorization', 'Bearer integration-secret')
                && $request['source_request_id'] === $supplyRequest->id
                && $request['source_request_number'] === $supplyRequest->request_number;
        });
    }
}

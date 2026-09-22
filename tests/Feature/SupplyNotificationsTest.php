<?php

namespace Tests\Feature;

use App\Models\SupplyClient;
use App\Models\SupplyIssueRequest;
use App\Models\User;
use App\Notifications\SupplyIssueNotification;
use App\Services\Supply\SupplyIssueNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplyNotificationsTest extends TestCase
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
        Role::findOrCreate('SUPERADMIN', 'web');
    }

    public function test_new_issue_request_notifies_active_supply_admins(): void
    {
        $admin = $this->supplyAdmin();
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->syncRoles(['SUPERADMIN']);
        $requester = $this->requester();
        $issueRequest = $this->issueRequestFor($requester);

        app(SupplyIssueNotificationService::class)->notifyAdminsOfNewRequest($issueRequest);

        $notification = $admin->notifications()->firstOrFail();

        $this->assertSame(SupplyIssueNotification::class, $notification->type);
        $this->assertSame(SupplyIssueNotification::EVENT_NEW_REQUEST, $notification->data['event']);
        $this->assertSame($issueRequest->id, $notification->data['issue_request_id']);
        $this->assertSame('/supplies/issues/' . $issueRequest->id, $notification->data['url']);
        $this->assertSame(
            SupplyIssueNotification::EVENT_NEW_REQUEST,
            $superAdmin->notifications()->firstOrFail()->data['event']
        );
    }

    public function test_marking_an_issue_ready_notifies_its_requester(): void
    {
        $admin = $this->supplyAdmin();
        $requester = $this->requester();
        $issueRequest = $this->issueRequestFor($requester);
        $product = \App\Models\SupplyProduct::query()->firstOrFail();
        $item = $issueRequest->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 2,
            'reserved_quantity' => 2,
            'delivered_quantity' => 0,
            'available_quantity_at_request' => 2,
        ]);

        $this->actingAs($admin)
            ->put(route('supplies.issues.ready', $issueRequest), [
                'delivered_quantity' => [$item->id => 2],
            ])
            ->assertRedirect(route('supplies.issues.show', $issueRequest));

        $notification = $requester->notifications()->firstOrFail();

        $this->assertSame(SupplyIssueNotification::EVENT_READY, $notification->data['event']);
        $this->assertSame($issueRequest->id, $notification->data['issue_request_id']);
    }

    public function test_user_can_only_read_and_mark_own_supply_notifications(): void
    {
        $requester = $this->requester();
        $otherRequester = $this->requester();
        $issueRequest = $this->issueRequestFor($requester);

        $requester->notify(new SupplyIssueNotification($issueRequest, SupplyIssueNotification::EVENT_READY));
        $notification = $requester->notifications()->firstOrFail();
        $this->assertTrue($requester->can('supplies.request'));

        $this->actingAs($requester)
            ->getJson(route('supplies.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $notification->id);

        $this->actingAs($otherRequester)
            ->patchJson(route('supplies.notifications.read', $notification))
            ->assertNotFound();

        $this->actingAs($requester)
            ->patchJson(route('supplies.notifications.read', $notification))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($requester)
            ->getJson(route('supplies.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications');
    }

    public function test_opening_issue_directly_marks_its_notifications_as_read(): void
    {
        $requester = $this->requester();
        $issueRequest = $this->issueRequestFor($requester);
        $requester->notify(new SupplyIssueNotification($issueRequest, SupplyIssueNotification::EVENT_READY));
        $notification = $requester->notifications()->firstOrFail();

        $this->actingAs($requester)
            ->get(route('supplies.issues.show', $issueRequest))
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($requester)
            ->getJson(route('supplies.notifications.index'))
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications');
    }

    private function supplyAdmin(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['PROVEEDURIA_ADMIN']);

        return $user->fresh();
    }

    private function requester(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->syncRoles(['PROVEEDURIA_USUARIO']);

        return $user->fresh();
    }

    private function issueRequestFor(User $requester): SupplyIssueRequest
    {
        $client = SupplyClient::query()->firstOrFail();

        return SupplyIssueRequest::create([
            'request_number' => 'TEST-NOT-' . uniqid(),
            'requested_by_user_id' => $requester->id,
            'supply_client_id' => $client->id,
            'status' => SupplyIssueRequest::STATUS_PREPARING,
            'requested_at' => now(),
        ]);
    }
}

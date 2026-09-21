<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Config::set('auth.two_factor_enabled', true);
    }

    public function test_user_can_save_own_theme_and_two_factor_preferences(): void
    {
        $user = User::factory()->create([
            'theme_mode' => 'system',
            'two_factor_enabled' => true,
        ]);

        $this->from(route('preferences.edit'))
            ->actingAs($user)
            ->withSession(['two_factor_verified' => true])
            ->put(route('preferences.update'), [
                'theme_mode' => 'dark',
                'two_factor_enabled' => false,
            ])
            ->assertRedirect(route('preferences.edit'));

        $user->refresh();

        $this->assertSame('dark', $user->theme_mode);
        $this->assertFalse($user->two_factor_enabled);
        $this->assertFalse($user->requiresTwoFactorAuthentication());
    }

    public function test_super_admin_can_manage_another_users_preferences(): void
    {
        $role = Role::findOrCreate('SUPERADMIN', 'web');
        $admin = User::factory()->create();
        $admin->syncRoles([$role]);
        $target = User::factory()->create([
            'theme_mode' => 'system',
            'two_factor_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['two_factor_verified' => true])
            ->put(route('profiles.preferences.update', $target), [
                'theme_mode' => 'light',
                'two_factor_enabled' => false,
            ])
            ->assertRedirect();

        $target->refresh();

        $this->assertSame('light', $target->theme_mode);
        $this->assertFalse($target->two_factor_enabled);
    }

    public function test_super_admin_can_view_supply_issue_schedule_preference(): void
    {
        $role = Role::findOrCreate('SUPERADMIN', 'web');
        $admin = User::factory()->create();
        $admin->syncRoles([$role]);

        $this->actingAs($admin)
            ->withSession(['two_factor_verified' => true])
            ->get(route('profiles.preferences.index'))
            ->assertOk()
            ->assertSee('Envio restringido de Proveeduria');
    }

    public function test_regular_user_cannot_access_preferences_administration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['two_factor_verified' => true])
            ->get(route('profiles.preferences.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_update_two_factor_for_all_users(): void
    {
        $originalValues = User::query()->pluck('two_factor_enabled', 'id');
        $role = Role::findOrCreate('SUPERADMIN', 'web');
        $admin = User::factory()->create(['two_factor_enabled' => true]);
        $admin->syncRoles([$role]);
        $target = User::factory()->create(['two_factor_enabled' => true]);

        try {
            $this->actingAs($admin)
                ->withSession(['two_factor_verified' => true])
                ->put(route('profiles.preferences.two-factor.update'), [
                    'two_factor_enabled' => false,
                ])
                ->assertRedirect();

            $admin->refresh();
            $target->refresh();

            $this->assertFalse($admin->two_factor_enabled);
            $this->assertFalse($target->two_factor_enabled);
        } finally {
            $originalValues->each(function ($enabled, $id): void {
                User::query()->whereKey($id)->update(['two_factor_enabled' => $enabled]);
            });
        }
    }

    public function test_super_admin_can_disable_supply_issue_schedule_restriction(): void
    {
        $role = Role::findOrCreate('SUPERADMIN', 'web');
        $admin = User::factory()->create();
        $admin->syncRoles([$role]);

        try {
            $this->actingAs($admin)
                ->withSession(['two_factor_verified' => true])
                ->put(route('profiles.preferences.supply-issue-schedule.update'), [
                    'supply_issue_schedule_restriction_enabled' => false,
                ])
                ->assertRedirect();

            $this->assertFalse(SystemSetting::boolean(SystemSetting::SUPPLY_ISSUE_SCHEDULE_RESTRICTION, true));
        } finally {
            SystemSetting::forgetCached(SystemSetting::SUPPLY_ISSUE_SCHEDULE_RESTRICTION);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticatedRedirectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_selection_ignores_a_stale_login_destination(): void
    {
        $role = Role::findOrCreate('SUPERADMIN', 'web');
        $customer = Customer::create([
            'name' => 'Cliente Retorno Seguro',
            'email' => 'return-safe@example.com',
            'phone' => '3000000011',
            'address' => 'Calle 11',
        ]);
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $user->syncRoles([$role]);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'url.intended' => route('login'),
        ])->post(route('customer.context.store'), [
            'customer' => $customer->name,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame($customer->name, session('selected_customer'));
        $this->assertSame([$customer->name], session('selected_customers'));
    }

    public function test_authenticated_user_visiting_login_is_returned_to_the_application(): void
    {
        $role = Role::findOrCreate('SUPERADMIN', 'web');
        $customer = Customer::create([
            'name' => 'Cliente Sesion Activa',
            'email' => 'active-session@example.com',
            'phone' => '3000000012',
            'address' => 'Calle 12',
        ]);
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $user->syncRoles([$role]);

        $this->actingAs($user)->withSession([
            'two_factor_verified' => true,
            'selected_customer' => $customer->name,
            'selected_customers' => [$customer->name],
        ])->get(route('login'))->assertRedirect(route('dashboard'));
    }

    public function test_normal_user_with_one_customer_is_still_asked_to_select_it(): void
    {
        $role = Role::findOrCreate('NORMAL_CUSTOMER_SELECTION_TEST', 'web');
        $customer = Customer::create([
            'name' => 'Cliente Seleccion Explicita',
            'email' => 'explicit-selection@example.com',
            'phone' => '3000000014',
            'address' => 'Calle 14',
        ]);
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $user->syncRoles([$role]);
        $user->customerAccesses()->attach($customer->customer_id);

        $this->actingAs($user)->withSession(['two_factor_verified' => true])
            ->get(route('login'))
            ->assertRedirect(route('customer.context.index'));
    }
}

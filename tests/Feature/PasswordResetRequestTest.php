<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetRequestTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unknown_email_is_rejected_before_a_reset_link_is_sent(): void
    {
        Password::shouldReceive('sendResetLink')->never();

        $this->postJson(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('errors.email.0', 'No existe una cuenta registrada con ese correo electronico.');
    }

    public function test_inactive_user_cannot_request_a_reset_link(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Password::shouldReceive('sendResetLink')->never();

        $this->postJson(route('password.email'), ['email' => $user->email])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'La cuenta esta inactiva. Contacte al administrador del sistema.');
    }

    public function test_active_registered_user_can_request_a_reset_link(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->postJson(route('password.email'), ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Enlace de recuperacion enviado correctamente.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_can_sign_in_and_reach_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk();
    }

    public function test_invalid_login_is_rejected(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-password']);

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_non_logistics_role_is_blocked_from_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->get('/')->assertForbidden();
    }
}

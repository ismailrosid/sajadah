<?php

namespace Tests\Feature\Auth;

use App\Enums\GlobalRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_user_can_register_personally_without_community(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ahmad Personal',
            'email' => 'ahmad@example.com',
            'city' => 'Surabaya',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'ahmad@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Ahmad Personal', $user->name);
        $this->assertEquals('Surabaya', $user->city);
        $this->assertEquals('Asia/Jakarta', $user->timezone);
        $this->assertEquals('KEMENAG', $user->prayer_calc_method);
        $this->assertEquals(60, $user->attendance_duration_minutes);
        $this->assertEquals(GlobalRole::User, $user->global_role);
    }

    public function test_public_registration_cannot_grant_admin_access(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'global_role' => 'admin',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'attacker@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(GlobalRole::User, $user->global_role);
        $this->assertFalse($user->isAdmin());
    }
}

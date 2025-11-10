<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user can view the login form.
     */
    public function test_user_can_view_login_form(): void
    {
        $response = $this->get(route('login.form'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test that user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that user cannot login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test that remember me checkbox works with value '1'.
     */
    public function test_remember_me_works_with_value_one(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // Check that remember token is set
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    /**
     * Test that remember me checkbox works with value '0'.
     */
    public function test_remember_me_works_with_value_zero(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
            'remember' => '0',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that remember me checkbox works with boolean true.
     */
    public function test_remember_me_works_with_boolean_true(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that remember me checkbox works with boolean false.
     */
    public function test_remember_me_works_with_boolean_false(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
            'remember' => false,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that remember field can be omitted (nullable).
     */
    public function test_remember_field_can_be_omitted(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that invalid remember values are rejected.
     */
    public function test_invalid_remember_value_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
            'remember' => 'invalid',
        ]);

        $response->assertSessionHasErrors('remember');
        $this->assertGuest();
    }

    /**
     * Test login with JSON request (API format).
     */
    public function test_user_can_login_via_json_request(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson(route('login'), [
            'username' => 'test@example.com',
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user',
            'redirect',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test that email field is required.
     */
    public function test_email_field_is_required(): void
    {
        $response = $this->post(route('login'), [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test that password field is required.
     */
    public function test_password_field_is_required(): void
    {
        $response = $this->post(route('login'), [
            'username' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /**
     * Test that email must be valid format.
     */
    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post(route('login'), [
            'username' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Test that user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user);

        $response = $this->delete(route('logout'));

        $response->assertRedirect(route('login.form'));
        $this->assertGuest();
    }
}

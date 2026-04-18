<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

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

    public function test_new_users_can_register(): void
    {
        // 新規登録ユーザーはcustomerロールなので customer.home にリダイレクトされる
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.home', absolute: false));
    }

    public function test_registration_regenerates_the_session(): void
    {
        $this->withSession([]);
        $previousSessionId = $this->app['session']->getId();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'rotated-session@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $this->assertAuthenticated();
        $this->assertNotSame($previousSessionId, $this->app['session']->getId());
        $response->assertRedirect(route('customer.home', absolute: false));
    }
}

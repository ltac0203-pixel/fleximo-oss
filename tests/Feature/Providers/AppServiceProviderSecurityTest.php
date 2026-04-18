<?php

declare(strict_types=1);

namespace Tests\Feature\Providers;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class AppServiceProviderSecurityTest extends TestCase
{
    private function invokeValidateProductionSessionSecurity(): void
    {
        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'validateProductionSessionSecurity');
        $method->invoke($provider);
    }

    public function test_production_throws_when_session_secure_cookie_is_false(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['session.secure' => false, 'session.encrypt' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SESSION_SECURE_COOKIE');

        $this->invokeValidateProductionSessionSecurity();
    }

    public function test_production_throws_when_session_encrypt_is_false(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['session.encrypt' => false, 'session.secure' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SESSION_ENCRYPT');

        $this->invokeValidateProductionSessionSecurity();
    }

    public function test_local_environment_does_not_throw(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config(['session.secure' => false, 'session.encrypt' => false]);

        $this->invokeValidateProductionSessionSecurity();

        $this->assertTrue(true);
    }

    public function test_boot_throws_in_production_even_when_running_in_console(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config([
            'session.secure' => false,
            'session.encrypt' => true,
            'fincode.webhook_secret' => 'dummy_secret_for_test',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SESSION_SECURE_COOKIE');

        (new AppServiceProvider($this->app))->boot();
    }
}

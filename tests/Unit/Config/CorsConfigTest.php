<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Tests\TestCase;

class CorsConfigTest extends TestCase
{
    private const DEFAULT_DEVELOPMENT_BASE_ORIGINS = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ];

    public function test_production_uses_cors_allowed_origins_env_when_provided(): void
    {
        $originalAppEnv = $this->getEnvironmentVariable('APP_ENV');
        $originalCorsAllowedOrigins = $this->getEnvironmentVariable('CORS_ALLOWED_ORIGINS');

        try {
            $this->setEnvironmentVariable('APP_ENV', 'production');
            $this->setEnvironmentVariable('CORS_ALLOWED_ORIGINS', 'https://app.example.com,https://www.example.com');

            $config = require base_path('config/cors.php');

            $this->assertSame(
                ['https://app.example.com', 'https://www.example.com'],
                $config['allowed_origins']
            );
        } finally {
            $this->setEnvironmentVariable('APP_ENV', $originalAppEnv);
            $this->setEnvironmentVariable('CORS_ALLOWED_ORIGINS', $originalCorsAllowedOrigins);
        }
    }

    public function test_production_falls_back_to_app_url_when_cors_env_not_set(): void
    {
        $originalAppEnv = $this->getEnvironmentVariable('APP_ENV');
        $originalCorsAllowedOrigins = $this->getEnvironmentVariable('CORS_ALLOWED_ORIGINS');
        $originalAppUrl = $this->getEnvironmentVariable('APP_URL');

        try {
            $this->setEnvironmentVariable('APP_ENV', 'production');
            $this->setEnvironmentVariable('CORS_ALLOWED_ORIGINS', '');
            $this->setEnvironmentVariable('APP_URL', 'https://app.example.com');

            $config = require base_path('config/cors.php');

            $this->assertSame(['https://app.example.com'], $config['allowed_origins']);
        } finally {
            $this->setEnvironmentVariable('APP_ENV', $originalAppEnv);
            $this->setEnvironmentVariable('CORS_ALLOWED_ORIGINS', $originalCorsAllowedOrigins);
            $this->setEnvironmentVariable('APP_URL', $originalAppUrl);
        }
    }

    public function test_local_environment_includes_dev_origins_and_app_url(): void
    {
        $originalAppEnv = $this->getEnvironmentVariable('APP_ENV');
        $originalCorsAllowedOrigins = $this->getEnvironmentVariable('CORS_ALLOWED_ORIGINS');
        $originalAppUrl = $this->getEnvironmentVariable('APP_URL');

        try {
            $this->setEnvironmentVariable('APP_ENV', 'local');
            $this->setEnvironmentVariable('CORS_ALLOWED_ORIGINS', '');
            $this->setEnvironmentVariable('APP_URL', 'https://app.example.com');

            $config = require base_path('config/cors.php');

            $expected = array_values(array_unique(array_merge(
                self::DEFAULT_DEVELOPMENT_BASE_ORIGINS,
                ['https://app.example.com']
            )));

            $this->assertSame($expected, $config['allowed_origins']);
        } finally {
            $this->setEnvironmentVariable('APP_ENV', $originalAppEnv);
            $this->setEnvironmentVariable('CORS_ALLOWED_ORIGINS', $originalCorsAllowedOrigins);
            $this->setEnvironmentVariable('APP_URL', $originalAppUrl);
        }
    }

    private function getEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);

        return $value === false ? null : $value;
    }

    private function setEnvironmentVariable(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureIdempotencyKeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['idempotent'])->post('/middleware/idempotency-test', function () {
            return response()->json(['status' => 'ok'], 201);
        });
    }

    public function test_accepts_standard_idempotency_key_header(): void
    {
        $key = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->withHeaders([
            'Idempotency-Key' => $key,
        ])->postJson('/middleware/idempotency-test', [
            'foo' => 'bar',
        ]);

        $response->assertCreated()
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('idempotency_keys', [
            'key' => $key,
        ]);
    }

    public function test_invalid_header_message_uses_standard_name(): void
    {
        $response = $this->withHeaders([
            'Idempotency-Key' => 'invalid-key',
        ])->postJson('/middleware/idempotency-test', [
            'foo' => 'bar',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Idempotency-Key must be a UUIDv4 string.',
            ]);
    }

    public function test_rejects_request_without_idempotency_key(): void
    {
        $response = $this->postJson('/middleware/idempotency-test', [
            'foo' => 'bar',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Idempotency-Key header is required.',
            ]);
    }
}

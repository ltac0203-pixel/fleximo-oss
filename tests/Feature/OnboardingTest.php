<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_column_defaults_to_null_for_new_users(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->onboarding_completed_at);
        $this->assertTrue($user->shouldShowOnboarding());
    }

    public function test_should_show_onboarding_is_false_after_completion(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this->assertFalse($user->shouldShowOnboarding());
    }

    public function test_complete_endpoint_stamps_onboarding_completed_at(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->onboarding_completed_at);

        $response = $this
            ->actingAs($user)
            ->post('/onboarding/complete');

        $response->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_complete_endpoint_is_idempotent(): void
    {
        $completedAt = now()->subDay();
        $user = User::factory()->create(['onboarding_completed_at' => $completedAt]);

        $this
            ->actingAs($user)
            ->post('/onboarding/complete')
            ->assertRedirect();

        $user->refresh();
        // 再完了で日時が上書きされないこと（最初に完了した時刻を保持）
        $this->assertEqualsWithDelta(
            $completedAt->timestamp,
            $user->onboarding_completed_at->timestamp,
            1,
        );
    }

    public function test_reset_endpoint_clears_onboarding_completed_at(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $this
            ->actingAs($user)
            ->post('/onboarding/reset')
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->onboarding_completed_at);
    }

    public function test_complete_requires_authentication(): void
    {
        $response = $this->post('/onboarding/complete');
        $response->assertStatus(302);
        $this->assertStringNotContainsString('/onboarding', $response->headers->get('Location', ''));
    }
}

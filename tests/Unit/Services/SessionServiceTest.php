<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SessionService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionService = new SessionService;
    }

    private function insertSession(int $userId, string $sessionId): void
    {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'TestBrowser/1.0',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_delete_other_sessions_keeps_current_session(): void
    {
        $user = User::factory()->create();
        $currentSessionId = 'current-session-'.Str::random(10);
        $otherSessionId1 = 'other-session-'.Str::random(10);
        $otherSessionId2 = 'other-session-'.Str::random(10);

        $this->insertSession($user->id, $currentSessionId);
        $this->insertSession($user->id, $otherSessionId1);
        $this->insertSession($user->id, $otherSessionId2);

        $deleted = $this->sessionService->deleteOtherSessions($user->id, $currentSessionId);

        $this->assertSame(2, $deleted);
        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId]);
        $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId1]);
        $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId2]);
    }

    public function test_delete_other_sessions_does_not_affect_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $currentSessionId = 'current-session-'.Str::random(10);
        $otherUserSessionId = 'other-user-session-'.Str::random(10);

        $this->insertSession($user->id, $currentSessionId);
        $this->insertSession($user->id, 'user-other-'.Str::random(10));
        $this->insertSession($otherUser->id, $otherUserSessionId);

        $this->sessionService->deleteOtherSessions($user->id, $currentSessionId);

        $this->assertDatabaseHas('sessions', ['id' => $currentSessionId]);
        $this->assertDatabaseHas('sessions', ['id' => $otherUserSessionId]);
    }

    public function test_delete_all_sessions_removes_all_user_sessions(): void
    {
        $user = User::factory()->create();
        $sessionId1 = 'session-1-'.Str::random(10);
        $sessionId2 = 'session-2-'.Str::random(10);

        $this->insertSession($user->id, $sessionId1);
        $this->insertSession($user->id, $sessionId2);

        $deleted = $this->sessionService->deleteAllSessions($user->id);

        $this->assertSame(2, $deleted);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_delete_all_sessions_does_not_affect_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUserSessionId = 'other-user-session-'.Str::random(10);

        $this->insertSession($user->id, 'user-session-'.Str::random(10));
        $this->insertSession($otherUser->id, $otherUserSessionId);

        $this->sessionService->deleteAllSessions($user->id);

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => $otherUserSessionId]);
    }
}

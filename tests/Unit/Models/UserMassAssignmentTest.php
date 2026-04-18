<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_status_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->customer()->create();

        $user->fill([
            'account_status' => AccountStatus::Banned,
            'account_status_reason' => '不正利用',
            'account_status_changed_at' => now(),
            'account_status_changed_by' => 1,
        ]);

        // fill() では account_status 関連が変更されないことを確認
        $this->assertNotEquals(AccountStatus::Banned, $user->account_status);
        $this->assertNull($user->account_status_reason);
    }

    public function test_account_status_can_be_changed_with_force_fill(): void
    {
        $user = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $user->forceFill([
            'account_status' => AccountStatus::Suspended,
            'account_status_reason' => 'テスト理由',
            'account_status_changed_at' => now(),
            'account_status_changed_by' => $admin->id,
        ])->save();

        $user->refresh();

        $this->assertEquals(AccountStatus::Suspended, $user->account_status);
        $this->assertEquals('テスト理由', $user->account_status_reason);
    }
}

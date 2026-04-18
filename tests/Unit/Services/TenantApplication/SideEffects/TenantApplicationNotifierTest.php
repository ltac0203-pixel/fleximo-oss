<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantApplication\SideEffects;

use App\Mail\NewTenantApplicationMail;
use App\Mail\TenantApplicationApprovedMail;
use App\Mail\TenantApplicationReceivedMail;
use App\Mail\TenantApplicationRejectedMail;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\TenantApplication\SideEffects\TenantApplicationNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class TenantApplicationNotifierTest extends TestCase
{
    use RefreshDatabase;

    private TenantApplicationNotifier $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = app(TenantApplicationNotifier::class);
    }

    public function test_notify_received_queues_mail_and_logs_success_context(): void
    {
        Mail::fake();
        Log::spy();

        $application = TenantApplication::factory()->create([
            'applicant_email' => 'received@example.com',
        ]);

        $this->notifier->notifyReceived($application, [
            'user_id' => 10,
            'tenant_id' => 20,
        ], '新フロー');

        Mail::assertQueued(TenantApplicationReceivedMail::class, function (TenantApplicationReceivedMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                'テナント申し込み受付メールをキューに追加しました（新フロー）',
                Mockery::on(function (array $context) use ($application): bool {
                    return ($context['application_id'] ?? null) === $application->id
                        && ($context['email'] ?? null) === 'received@example.com'
                        && ($context['user_id'] ?? null) === 10
                        && ($context['tenant_id'] ?? null) === 20;
                })
            );
    }

    public function test_notify_new_application_to_admin_queues_mail_and_logs_success_context(): void
    {
        Config::set('mail.admin_email', 'admin@example.com');
        Mail::fake();
        Log::spy();

        $application = TenantApplication::factory()->create();

        $this->notifier->notifyNewApplicationToAdmin($application, '新フロー');

        Mail::assertQueued(NewTenantApplicationMail::class, function (NewTenantApplicationMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                '新規テナント申し込み管理者通知メールをキューに追加しました（新フロー）',
                Mockery::on(function (array $context) use ($application): bool {
                    return ($context['application_id'] ?? null) === $application->id
                        && ($context['admin_email'] ?? null) === 'admin@example.com';
                })
            );
    }

    public function test_notify_new_application_to_admin_logs_warning_when_admin_email_is_missing(): void
    {
        Config::set('mail.admin_email', null);
        Mail::fake();
        Log::spy();

        $application = TenantApplication::factory()->create();

        $this->notifier->notifyNewApplicationToAdmin($application);

        Mail::assertNotQueued(NewTenantApplicationMail::class);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                '管理者メールアドレスが設定されていません（MAIL_ADMIN_ADDRESS）',
                Mockery::on(fn (array $context): bool => ($context['application_id'] ?? null) === $application->id)
            );
    }

    public function test_notify_received_logs_error_when_enqueue_fails(): void
    {
        Log::spy();

        $application = TenantApplication::factory()->create([
            'applicant_email' => 'received-fail@example.com',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('received-fail@example.com')
            ->andThrow(new \RuntimeException('queue failed'));

        $this->notifier->notifyReceived($application);

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'テナント申し込み受付メールのキュー追加に失敗しました',
                Mockery::on(function (array $context) use ($application): bool {
                    return ($context['application_id'] ?? null) === $application->id
                        && ($context['email'] ?? null) === 'received-fail@example.com'
                        && ($context['error'] ?? null) === 'queue failed';
                })
            );
    }

    public function test_notify_approved_queues_mail_and_logs_success_context(): void
    {
        Mail::fake();
        Log::spy();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin()->create([
            'email' => 'approved@example.com',
        ]);
        $application = TenantApplication::factory()->create([
            'applicant_email' => $user->email,
        ]);

        $this->notifier->notifyApproved($application, $tenant, $user, 'token-123', '旧フロー');

        Mail::assertQueued(TenantApplicationApprovedMail::class, function (TenantApplicationApprovedMail $mail) use ($application, $tenant, $user): bool {
            return $mail->application->is($application)
                && $mail->tenant->is($tenant)
                && $mail->user->is($user)
                && $mail->requiresPasswordReset === true
                && $mail->passwordResetUrl !== null;
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                'テナント申し込み承認メールをキューに追加しました（旧フロー）',
                Mockery::on(function (array $context) use ($application, $tenant, $user): bool {
                    return ($context['application_id'] ?? null) === $application->id
                        && ($context['tenant_id'] ?? null) === $tenant->id
                        && ($context['user_id'] ?? null) === $user->id
                        && ($context['email'] ?? null) === $application->applicant_email;
                })
            );
    }

    public function test_notify_rejected_queues_mail_and_logs_success_context(): void
    {
        Mail::fake();
        Log::spy();

        $application = TenantApplication::factory()->create([
            'applicant_email' => 'rejected@example.com',
        ]);

        $this->notifier->notifyRejected($application, '審査基準を満たしませんでした');

        Mail::assertQueued(TenantApplicationRejectedMail::class, function (TenantApplicationRejectedMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                'テナント申し込み却下メールをキューに追加しました',
                Mockery::on(function (array $context) use ($application): bool {
                    return ($context['application_id'] ?? null) === $application->id
                        && ($context['email'] ?? null) === 'rejected@example.com'
                        && ($context['reason'] ?? null) === '審査基準を満たしませんでした';
                })
            );
    }
}

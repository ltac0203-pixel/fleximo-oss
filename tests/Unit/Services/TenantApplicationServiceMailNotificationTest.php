<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CreateTenantApplicationData;
use App\DTOs\Tenant\CreateTenantApplicationWithUserData;
use App\Enums\BusinessType;
use App\Mail\NewTenantApplicationMail;
use App\Mail\TenantApplicationReceivedMail;
use App\Services\TenantApplicationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class TenantApplicationServiceMailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_application_queues_applicant_and_admin_mail_when_admin_email_exists(): void
    {
        Config::set('mail.admin_email', 'admin@example.com');
        Mail::fake();

        $service = app(TenantApplicationService::class);
        $application = $service->createApplication($this->createApplicationData('applicant@example.com'));

        Mail::assertQueued(TenantApplicationReceivedMail::class, function (TenantApplicationReceivedMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });

        Mail::assertQueued(NewTenantApplicationMail::class, function (NewTenantApplicationMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });
    }

    public function test_create_application_logs_warning_and_skips_admin_mail_when_admin_email_missing(): void
    {
        Config::set('mail.admin_email', null);
        Mail::fake();
        Log::spy();

        $service = app(TenantApplicationService::class);
        $application = $service->createApplication($this->createApplicationData('no-admin@applicant.test'));

        Mail::assertQueued(TenantApplicationReceivedMail::class);
        Mail::assertNotQueued(NewTenantApplicationMail::class);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                '管理者メールアドレスが設定されていません（MAIL_ADMIN_ADDRESS）',
                Mockery::on(fn (array $context): bool => ($context['application_id'] ?? null) === $application->id)
            );
    }

    public function test_create_application_with_user_queues_applicant_and_admin_mail_when_admin_email_exists(): void
    {
        Config::set('mail.admin_email', 'admin@example.com');
        Mail::fake();
        Event::fake([Registered::class]);

        $service = app(TenantApplicationService::class);
        $result = $service->createApplicationWithUser($this->createApplicationWithUserData('newflow@applicant.test'));
        $application = $result['application'];
        $user = $result['user'];

        Mail::assertQueued(TenantApplicationReceivedMail::class, function (TenantApplicationReceivedMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });

        Mail::assertQueued(NewTenantApplicationMail::class, function (NewTenantApplicationMail $mail) use ($application): bool {
            return $mail->application->is($application);
        });

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_create_application_with_user_logs_warning_and_skips_admin_mail_when_admin_email_missing(): void
    {
        Config::set('mail.admin_email', null);
        Mail::fake();
        Event::fake([Registered::class]);
        Log::spy();

        $service = app(TenantApplicationService::class);
        $result = $service->createApplicationWithUser($this->createApplicationWithUserData('newflow-no-admin@applicant.test'));
        $application = $result['application'];

        Mail::assertQueued(TenantApplicationReceivedMail::class);
        Mail::assertNotQueued(NewTenantApplicationMail::class);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with(
                '管理者メールアドレスが設定されていません（MAIL_ADMIN_ADDRESS）',
                Mockery::on(fn (array $context): bool => ($context['application_id'] ?? null) === $application->id)
            );
    }

    public function test_create_application_logs_error_when_applicant_mail_enqueue_fails(): void
    {
        Config::set('mail.admin_email', null);
        Log::spy();

        Mail::shouldReceive('to')
            ->once()
            ->with('applicant@example.com')
            ->andThrow(new \RuntimeException('applicant send failed'));

        $service = app(TenantApplicationService::class);
        $service->createApplication($this->createApplicationData('applicant@example.com'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                'テナント申し込み受付メールのキュー追加に失敗しました',
                Mockery::on(function (array $context): bool {
                    return ($context['email'] ?? null) === 'applicant@example.com'
                        && ($context['error'] ?? null) === 'applicant send failed'
                        && isset($context['application_id']);
                })
            );
    }

    public function test_create_application_logs_error_when_admin_mail_enqueue_fails(): void
    {
        Config::set('mail.admin_email', 'admin@example.com');
        Log::spy();

        $successPendingMail = new class
        {
            public function queue(mixed $mailable): void {}
        };

        $failingPendingMail = new class
        {
            public function queue(mixed $mailable): void
            {
                throw new \RuntimeException('admin send failed');
            }
        };

        Mail::shouldReceive('to')
            ->once()
            ->with('admin-fail@applicant.test')
            ->andReturn($successPendingMail);

        Mail::shouldReceive('to')
            ->once()
            ->with('admin@example.com')
            ->andReturn($failingPendingMail);

        $service = app(TenantApplicationService::class);
        $service->createApplication($this->createApplicationData('admin-fail@applicant.test'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                '新規テナント申し込み管理者通知メールのキュー追加に失敗しました',
                Mockery::on(function (array $context): bool {
                    return ($context['admin_email'] ?? null) === 'admin@example.com'
                        && ($context['error'] ?? null) === 'admin send failed'
                        && isset($context['application_id']);
                })
            );
    }

    private function createApplicationData(string $email): CreateTenantApplicationData
    {
        return new CreateTenantApplicationData(
            applicant_name: 'テスト申請者',
            applicant_email: $email,
            applicant_phone: '090-1111-2222',
            tenant_name: 'テスト店舗',
            business_type: BusinessType::Restaurant->value,
            tenant_address: '東京都テスト区1-2-3',
        );
    }

    private function createApplicationWithUserData(string $email): CreateTenantApplicationWithUserData
    {
        return new CreateTenantApplicationWithUserData(
            applicant_name: '新フローテスト申請者',
            applicant_email: $email,
            applicant_phone: '090-3333-4444',
            tenant_name: '新フローテスト店舗',
            business_type: BusinessType::Restaurant->value,
            password: 'password123',
            tenant_address: '大阪府テスト市4-5-6',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Services\TenantApplication\SideEffects;

use App\Mail\NewTenantApplicationMail;
use App\Mail\TenantApplicationApprovedMail;
use App\Mail\TenantApplicationReceivedMail;
use App\Mail\TenantApplicationRejectedMail;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TenantApplicationNotifier
{
    public function notifyReceived(
        TenantApplication $application,
        array $extraContext = [],
        ?string $flowLabel = null
    ): void {
        $messageSuffix = $this->formatFlowSuffix($flowLabel);

        $this->queueMailWithLogging(
            recipientEmail: $application->applicant_email,
            mailable: new TenantApplicationReceivedMail($application),
            successMessage: "テナント申し込み受付メールをキューに追加しました{$messageSuffix}",
            errorMessage: "テナント申し込み受付メールのキュー追加に失敗しました{$messageSuffix}",
            successContext: array_merge([
                'application_id' => $application->id,
                'email' => $application->applicant_email,
            ], $extraContext),
            errorContext: array_merge([
                'application_id' => $application->id,
                'email' => $application->applicant_email,
            ], $extraContext),
        );
    }

    public function notifyNewApplicationToAdmin(TenantApplication $application, ?string $flowLabel = null): void
    {
        $adminEmail = config('mail.admin_email');
        if (! $adminEmail) {
            Log::warning('管理者メールアドレスが設定されていません（MAIL_ADMIN_ADDRESS）', [
                'application_id' => $application->id,
            ]);

            return;
        }

        $adminEmail = (string) $adminEmail;
        $messageSuffix = $this->formatFlowSuffix($flowLabel);

        $this->queueMailWithLogging(
            recipientEmail: $adminEmail,
            mailable: new NewTenantApplicationMail($application),
            successMessage: "新規テナント申し込み管理者通知メールをキューに追加しました{$messageSuffix}",
            errorMessage: "新規テナント申し込み管理者通知メールのキュー追加に失敗しました{$messageSuffix}",
            successContext: [
                'application_id' => $application->id,
                'admin_email' => $adminEmail,
            ],
            errorContext: [
                'application_id' => $application->id,
                'admin_email' => $adminEmail,
            ],
        );
    }

    public function notifyApproved(
        TenantApplication $application,
        Tenant $tenant,
        User $user,
        ?string $token,
        string $flowLabel
    ): void {
        $messageSuffix = $this->formatFlowSuffix($flowLabel);

        $this->queueMailWithLogging(
            recipientEmail: $application->applicant_email,
            mailable: new TenantApplicationApprovedMail($application, $tenant, $user, $token),
            successMessage: "テナント申し込み承認メールをキューに追加しました{$messageSuffix}",
            errorMessage: "テナント申し込み承認メールのキュー追加に失敗しました{$messageSuffix}",
            successContext: [
                'application_id' => $application->id,
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'email' => $application->applicant_email,
            ],
            errorContext: [
                'application_id' => $application->id,
                'email' => $application->applicant_email,
            ],
        );
    }

    public function notifyRejected(TenantApplication $application, string $reason): void
    {
        $this->queueMailWithLogging(
            recipientEmail: $application->applicant_email,
            mailable: new TenantApplicationRejectedMail($application),
            successMessage: 'テナント申し込み却下メールをキューに追加しました',
            errorMessage: 'テナント申し込み却下メールのキュー追加に失敗しました',
            successContext: [
                'application_id' => $application->id,
                'email' => $application->applicant_email,
                'reason' => $reason,
            ],
            errorContext: [
                'application_id' => $application->id,
                'email' => $application->applicant_email,
            ],
        );
    }

    private function queueMailWithLogging(
        string $recipientEmail,
        Mailable $mailable,
        string $successMessage,
        string $errorMessage,
        array $successContext,
        array $errorContext
    ): void {
        try {
            Mail::to($recipientEmail)->queue($mailable);
            Log::info($successMessage, $successContext);
        } catch (\Throwable $e) {
            Log::error($errorMessage, array_merge($errorContext, [
                'error' => $e->getMessage(),
            ]));
        }
    }

    private function formatFlowSuffix(?string $flowLabel): string
    {
        if ($flowLabel === null || $flowLabel === '') {
            return '';
        }

        return "（{$flowLabel}）";
    }
}

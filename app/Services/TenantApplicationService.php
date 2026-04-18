<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateTenantApplicationData;
use App\DTOs\Tenant\CreateTenantApplicationWithUserData;
use App\Enums\AuditAction;
use App\Enums\TenantApplicationStatus;
use App\Enums\TenantStatus;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantApplication\Commands\ApproveTenantApplicationService;
use App\Services\TenantApplication\Commands\RejectTenantApplicationService;
use App\Services\TenantApplication\Commands\StartReviewTenantApplicationService;
use App\Services\TenantApplication\SideEffects\TenantApplicationNotifier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantApplicationService
{
    public function __construct(
        private StartReviewTenantApplicationService $startReviewService,
        private ApproveTenantApplicationService $approveService,
        private RejectTenantApplicationService $rejectService,
        private TenantApplicationNotifier $notifier,
    ) {}

    public function getApplications(
        ?TenantApplicationStatus $status = null,
        ?string $search = null,
        int $perPage = 20,
        ?string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        return TenantApplication::query()
            ->status($status)
            ->search($search)
            ->with(['reviewer'])
            ->sort($sortBy, $sortDir)
            ->paginate($perPage);
    }

    // 申し込みを作成する（従来の方式：申し込みのみ作成）
    public function createApplication(CreateTenantApplicationData $data): TenantApplication
    {
        $application = new TenantApplication([
            'applicant_name' => $data->applicant_name,
            'applicant_email' => $data->applicant_email,
            'applicant_phone' => $data->applicant_phone,
            'tenant_name' => $data->tenant_name,
            'tenant_address' => $data->tenant_address,
            'business_type' => $data->business_type,
        ]);
        $application->status = TenantApplicationStatus::Pending;
        $application->save();

        // 申請者にフィードバックを即座に返し、申し込みが受理されたことを保証するため
        $this->notifier->notifyReceived($application);

        // 管理者が新規申し込みを見逃さないよう、即時に通知する
        $this->notifier->notifyNewApplicationToAdmin($application);

        return $application;
    }

    // 申し込みと同時にユーザー・テナントを作成する（新フロー）
    public function createApplicationWithUser(CreateTenantApplicationWithUserData $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. 申し込み時点でアカウントを確保し、承認後すぐにログインできるようにする
            $user = User::create([
                'name' => $data->applicant_name,
                'email' => $data->applicant_email,
                'password' => $data->password,
                'phone' => $data->applicant_phone,
            ]);
            $user->is_active = true;
            $user->role = UserRole::TenantAdmin;
            $user->save();

            // メール認証メールを送信する（AppServiceProviderで日本語化済み）
            event(new Registered($user));

            // 2. 承認前にテナント枠を確保し、slug の一意性を申し込み時点で保証する
            // レースコンディション対策: ユニーク制約違反時はランダムサフィックスでリトライ
            $tenant = $this->createTenantWithUniqueSlug($data);

            // 3. ユーザーとテナントの関連を即座に確立し、承認後のセットアップ手順を省略する
            $tenantUser = new TenantUser([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]);
            $tenantUser->role = TenantUserRole::Admin;
            $tenantUser->save();

            // 4. 申し込みレコードに作成済みテナント・ユーザーを紐付け、承認時に再作成を回避する
            $application = new TenantApplication([
                'applicant_name' => $data->applicant_name,
                'applicant_email' => $data->applicant_email,
                'applicant_phone' => $data->applicant_phone,
                'tenant_name' => $data->tenant_name,
                'tenant_address' => $data->tenant_address,
                'business_type' => $data->business_type,
            ]);
            $application->status = TenantApplicationStatus::Pending;
            $application->created_tenant_id = $tenant->id;
            $application->applicant_user_id = $user->id;
            $application->save();

            // 5. 申請者にフィードバックを返し、申し込みが受理されたことを保証する
            $this->notifier->notifyReceived(
                application: $application,
                extraContext: [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ],
                flowLabel: '新フロー',
            );

            // 6. 管理者が新規申し込みを見逃さないよう、即時に通知する
            $this->notifier->notifyNewApplicationToAdmin(
                application: $application,
                flowLabel: '新フロー',
            );

            // 7. コンプライアンス要件に基づき、テナント作成の経緯を追跡可能にする
            AuditLogger::log(
                action: AuditAction::TenantCreated,
                target: $tenant,
                changes: [
                    'metadata' => [
                        'source' => 'tenant_application_with_user',
                        'application_id' => $application->id,
                        'user_id' => $user->id,
                        'is_approved' => false,
                    ],
                ],
                tenantId: $tenant->id
            );

            return [
                'user' => $user,
                'tenant' => $tenant,
                'application' => $application,
            ];
        });
    }

    // 審査を開始する
    public function startReview(TenantApplication $application, User $reviewer): TenantApplication
    {
        return $this->startReviewService->handle($application, $reviewer);
    }

    // 申し込みを承認する
    public function approve(TenantApplication $application, User $reviewer): TenantApplication
    {
        return $this->approveService->handle($application, $reviewer);
    }

    // 申し込みを却下する
    public function reject(TenantApplication $application, User $reviewer, string $reason): TenantApplication
    {
        return $this->rejectService->handle($application, $reviewer, $reason);
    }

    // 内部メモを更新する
    public function updateInternalNotes(TenantApplication $application, ?string $notes): TenantApplication
    {
        $application->update([
            'internal_notes' => $notes,
        ]);

        return $application;
    }

    // ユニークなslugを生成する
    // DBユニーク制約により同時リクエストでの重複を防止し、衝突時はランダムサフィックスでリトライする
    private function generateUniqueSlug(string $name): string
    {
        // 日本語テナント名はSlug変換できないため、ASCII化可能な部分のみ使用する
        $baseSlug = Str::slug($name);

        // 全角文字のみの名前ではSlugが空になるため、フォールバックでURL安全な値を保証する
        if (empty($baseSlug)) {
            $baseSlug = 'tenant';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // レースコンディション対策: exists()とcreateの間で他リクエストが同一slugを挿入した場合、
        // DBユニーク制約違反が発生する。呼び出し元でキャッチしてリトライすること。
        return $slug;
    }

    private function createTenantWithUniqueSlug(CreateTenantApplicationWithUserData $data): Tenant
    {
        $maxRetries = 3;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $slug = $attempt === 0
                ? $this->generateUniqueSlug($data->tenant_name)
                : $this->generateUniqueSlug($data->tenant_name).'-'.Str::random(4);

            try {
                $tenant = new Tenant([
                    'name' => $data->tenant_name,
                    'slug' => $slug,
                    'address' => $data->tenant_address,
                    'email' => $data->applicant_email,
                    'phone' => $data->applicant_phone,
                ]);
                $tenant->is_active = false;
                $tenant->status = TenantStatus::Pending;
                $tenant->is_approved = false;
                $tenant->save();

                return $tenant;
            } catch (QueryException $e) {
                if ($attempt === $maxRetries || ! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }
        }

        // @codeCoverageIgnoreStart
        throw new \RuntimeException('Failed to generate unique slug after retries.');
        // @codeCoverageIgnoreEnd
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        // MySQL/MariaDB: 23000 (integrity constraint violation), error code 1062 (duplicate entry)
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }

    public function getDashboardStats(): array
    {
        return [
            'pending_count' => TenantApplication::where('status', TenantApplicationStatus::Pending)->count(),
            'under_review_count' => TenantApplication::where('status', TenantApplicationStatus::UnderReview)->count(),
            'approved_count' => TenantApplication::where('status', TenantApplicationStatus::Approved)->count(),
            'rejected_count' => TenantApplication::where('status', TenantApplicationStatus::Rejected)->count(),
            'total_count' => TenantApplication::count(),
            'active_tenant_count' => Tenant::where('is_active', true)->count(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BusinessType;
use App\Http\Requests\TenantApplicationRequest;
use App\Services\TenantApplicationService;
use App\Support\Seo\PublicPageSeoFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantApplicationController extends Controller
{
    public function __construct(
        private readonly TenantApplicationService $applicationService,
        private readonly PublicPageSeoFactory $publicPageSeoFactory
    ) {}

    // 申し込みフォームを表示する
    public function create(): Response
    {
        return Inertia::render('TenantApplication/Create', [
            'businessTypes' => BusinessType::options(),
            ...$this->publicPageSeoFactory->tenantApplication(),
        ]);
    }

    // 申し込みを送信する
    public function store(TenantApplicationRequest $request): RedirectResponse
    {
        $result = $this->applicationService->createApplicationWithUser($request->toDto());

        // 自動ログイン
        Auth::login($result['user']);

        // 申し込み完了画面で認証メール確認を案内する
        return redirect()->route('tenant-application.complete');
    }

    // 申し込み完了画面を表示する
    public function complete(): Response
    {
        return Inertia::render('TenantApplication/Complete', [
            ...$this->publicPageSeoFactory->tenantApplicationComplete(),
        ]);
    }
}

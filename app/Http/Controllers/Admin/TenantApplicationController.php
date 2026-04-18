<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TenantApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListApplicationsRequest;
use App\Http\Requests\Admin\RejectApplicationRequest;
use App\Http\Requests\Admin\UpdateApplicationNotesRequest;
use App\Http\Resources\TenantApplicationResource;
use App\Models\TenantApplication;
use App\Services\TenantApplicationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenantApplicationController extends Controller
{
    public function __construct(
        private TenantApplicationService $applicationService
    ) {}

    // 申し込み一覧を表示する
    public function index(ListApplicationsRequest $request): Response
    {
        $this->authorize('viewAny', TenantApplication::class);

        $status = $request->validated('status');
        $statusEnum = $status ? TenantApplicationStatus::tryFrom($status) : null;
        $search = $request->validated('search');
        $sortBy = $request->validated('sort', 'created_at');
        $sortDir = $request->validated('sort_dir', 'desc');

        $applications = $this->applicationService->getApplications(
            status: $statusEnum,
            search: $search,
            perPage: 20,
            sortBy: $sortBy,
            sortDir: $sortDir
        );

        return Inertia::render('Admin/Applications/Index', [
            'applications' => TenantApplicationResource::collection($applications),
            'statusFilter' => $status,
            'searchQuery' => $search,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'statuses' => collect(TenantApplicationStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    // 申し込み詳細を表示する
    public function show(TenantApplication $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['reviewer', 'createdTenant']);

        return Inertia::render('Admin/Applications/Show', [
            'application' => TenantApplicationResource::make($application)->resolve(),
        ]);
    }

    // 審査を開始する
    public function startReview(TenantApplication $application): RedirectResponse
    {
        $this->authorize('startReview', $application);

        try {
            $this->applicationService->startReview($application, auth()->user());

            return back()->with('success', '審査を開始しました');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '審査開始中にエラーが発生しました。管理者にお問い合わせください。');
        }
    }

    // 申し込みを承認する
    public function approve(TenantApplication $application): RedirectResponse
    {
        $this->authorize('approve', $application);

        try {
            $this->applicationService->approve($application, auth()->user());

            return back()->with('success', '申し込みを承認し、テナントを作成しました');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '承認処理中にエラーが発生しました。管理者にお問い合わせください。');
        }
    }

    // 申し込みを却下する
    public function reject(TenantApplication $application, RejectApplicationRequest $request): RedirectResponse
    {
        $this->authorize('reject', $application);

        try {
            $this->applicationService->reject(
                $application,
                auth()->user(),
                $request->validated('rejection_reason')
            );

            return back()->with('success', '申し込みを却下しました');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '却下処理中にエラーが発生しました。管理者にお問い合わせください。');
        }
    }

    // 内部メモを更新する
    public function updateNotes(TenantApplication $application, UpdateApplicationNotesRequest $request): RedirectResponse
    {
        $this->authorize('updateNotes', $application);

        $this->applicationService->updateInternalNotes(
            $application,
            $request->validated('internal_notes')
        );

        return back()->with('success', 'メモを更新しました');
    }
}

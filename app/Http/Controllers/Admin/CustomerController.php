<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanCustomerRequest;
use App\Http\Requests\Admin\ExportCustomerDataRequest;
use App\Http\Requests\Admin\ListCustomerOrdersRequest;
use App\Http\Requests\Admin\ListCustomersRequest;
use App\Http\Requests\Admin\SuspendCustomerRequest;
use App\Http\Resources\Admin\CustomerDetailResource;
use App\Http\Resources\Admin\CustomerListResource;
use App\Http\Resources\Admin\CustomerOrderResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Admin\CustomerDataExportService;
use App\Services\Admin\CustomerManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerManagementService $customerService,
        private CustomerDataExportService $exportService
    ) {}

    // 顧客一覧を表示する
    public function index(ListCustomersRequest $request): Response
    {
        Gate::authorize('admin.customer.viewAny');

        $status = $request->validated('status');
        $statusEnum = $status ? AccountStatus::tryFrom($status) : null;
        $search = $request->validated('search');
        $sortBy = $request->validated('sort', 'created_at');
        $sortDir = $request->validated('sort_dir', 'desc');

        $customers = $this->customerService->getCustomers(
            status: $statusEnum,
            search: $search,
            perPage: 20,
            sortBy: $sortBy,
            sortDir: $sortDir
        );

        return Inertia::render('Admin/Customers/Index', [
            'customers' => CustomerListResource::collection($customers),
            'statusFilter' => $status,
            'searchQuery' => $search,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'statuses' => collect(AccountStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    // 顧客詳細を表示する
    public function show(Request $request, User $customer): Response
    {
        Gate::authorize('admin.customer.view', $customer);

        $customer = $this->customerService->getCustomerDetail($customer);

        $recentOrders = $this->customerService->getCustomerOrders($customer, perPage: 5);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => CustomerDetailResource::make($customer)->resolve(),
            'recentOrders' => CustomerOrderResource::collection($recentOrders)->resolve(),
        ]);
    }

    // 顧客の注文履歴を表示する
    public function orders(ListCustomerOrdersRequest $request, User $customer): Response
    {
        Gate::authorize('admin.customer.view', $customer);

        $tenantId = $request->validated('tenant_id');
        $orderStatus = $request->validated('order_status');

        $orders = $this->customerService->getCustomerOrders(
            customer: $customer,
            perPage: 20,
            tenantId: $tenantId ? (int) $tenantId : null,
            orderStatus: $orderStatus
        );

        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Customers/Orders', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
            ],
            'orders' => CustomerOrderResource::collection($orders),
            'tenants' => $tenants,
            'filters' => [
                'tenant_id' => $tenantId,
                'order_status' => $orderStatus,
            ],
        ]);
    }

    // 顧客を一時停止する
    public function suspend(SuspendCustomerRequest $request, User $customer): RedirectResponse
    {
        try {
            $this->customerService->suspendCustomer(
                $customer,
                $request->validated('reason'),
                $request->user()
            );

            return back()->with('success', '顧客アカウントを一時停止しました。');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '一時停止処理中にエラーが発生しました。');
        }
    }

    // 顧客をBANする
    public function ban(BanCustomerRequest $request, User $customer): RedirectResponse
    {
        try {
            $this->customerService->banCustomer(
                $customer,
                $request->validated('reason'),
                $request->user()
            );

            return back()->with('success', '顧客アカウントをBANしました。');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'BAN処理中にエラーが発生しました。');
        }
    }

    // 顧客を再有効化する
    public function reactivate(Request $request, User $customer): RedirectResponse
    {
        Gate::authorize('admin.customer.reactivate', $customer);

        try {
            $this->customerService->reactivateCustomer($customer, $request->user());

            return back()->with('success', '顧客アカウントを再有効化しました。');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '再有効化処理中にエラーが発生しました。');
        }
    }

    // 顧客データをエクスポートする
    public function export(ExportCustomerDataRequest $request, User $customer): StreamedResponse
    {
        $format = $request->validated('format');
        $exportData = $this->exportService->buildExportData($customer);

        if ($format === 'json') {
            return response()->streamDownload(function () use ($exportData) {
                echo json_encode($exportData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }, "customer_{$customer->id}_export.json", [
                'Content-Type' => 'application/json',
            ]);
        }

        return response()->streamDownload(function () use ($exportData) {
            $this->exportService->streamCsv($exportData);
        }, "customer_{$customer->id}_export.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Enums\OrderStatus;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderCompletedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\KdsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendOrderNotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $staff;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->tenant = Tenant::factory()->create();

        $this->staff = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->customer = User::factory()->customer()->create();
    }

    public function test_kds_completed_triggers_order_completed_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->ready()
            ->create();

        OrderItem::factory()->forOrder($order)->create();

        $kdsService = app(KdsService::class);
        $kdsService->updateOrderStatus($order, OrderStatus::Completed);

        Mail::assertQueued(OrderCompletedMail::class, function (OrderCompletedMail $mail) {
            return $mail->hasTo($this->customer->email);
        });
    }

    public function test_kds_cancelled_triggers_order_cancelled_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->accepted()
            ->create();

        OrderItem::factory()->forOrder($order)->create();

        $kdsService = app(KdsService::class);
        $kdsService->updateOrderStatus($order, OrderStatus::Cancelled);

        Mail::assertQueued(OrderCancelledMail::class, function (OrderCancelledMail $mail) {
            return $mail->hasTo($this->customer->email);
        });
    }

    public function test_kds_accepted_does_not_trigger_mail(): void
    {
        $order = Order::factory()
            ->forUser($this->customer)
            ->forTenant($this->tenant)
            ->paid()
            ->create();

        $kdsService = app(KdsService::class);
        $kdsService->updateOrderStatus($order, OrderStatus::Accepted);

        Mail::assertNothingQueued();
    }
}

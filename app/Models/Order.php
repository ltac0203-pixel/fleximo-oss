<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Scopes\TenantScope;
use App\Models\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @property OrderStatus $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $in_progress_at
 * @property \Illuminate\Support\Carbon|null $ready_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 */
class Order extends Model
{
    use BelongsToTenant, HasFactory;

    // user_id, tenant_id, status, total_amount はService層(OrderCreationService)でのみ設定される。
    // $fillableから除外し、直接属性代入で書き込む。
    // Controllerで直接Order::create()しないこと。
    protected $fillable = [
        'order_code',
        'business_date',
        'payment_id',
        'paid_at',
        'accepted_at',
        'in_progress_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'business_date' => 'date',
            'total_amount' => 'integer',
            'paid_at' => 'datetime',
            'accepted_at' => 'datetime',
            'in_progress_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isPendingPayment(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    public function isAccepted(): bool
    {
        return $this->status === OrderStatus::Accepted;
    }

    public function isInProgress(): bool
    {
        return $this->status === OrderStatus::InProgress;
    }

    public function isReady(): bool
    {
        return $this->status === OrderStatus::Ready;
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function isPaymentFailed(): bool
    {
        return $this->status === OrderStatus::PaymentFailed;
    }

    public function isRefunded(): bool
    {
        return $this->status === OrderStatus::Refunded;
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    // 決済処理と KDS 更新が同じ注文を触るため、遷移可否の判定と更新を同じロック内に閉じ込める。
    public function transitionTo(OrderStatus $newStatus, array $attributes = []): void
    {
        DB::transaction(function () use ($newStatus, $attributes) {
            $fresh = static::lockForUpdate()->findOrFail($this->id);

            if (! $fresh->status->canTransitionTo($newStatus)) {
                throw new InvalidArgumentException(
                    "Cannot transition from {$fresh->status->value} to {$newStatus->value}"
                );
            }

            $this->status = $newStatus;
            foreach ($attributes as $key => $value) {
                $this->{$key} = $value;
            }
            $this->save();
        });
    }

    public function markAsPaid(): void
    {
        $this->transitionTo(OrderStatus::Paid, ['paid_at' => now()]);
    }

    public function markAsPaymentFailed(): void
    {
        $this->transitionTo(OrderStatus::PaymentFailed);
    }

    public function markAsAccepted(): void
    {
        $this->transitionTo(OrderStatus::Accepted, ['accepted_at' => now()]);
    }

    public function markAsInProgress(): void
    {
        $this->transitionTo(OrderStatus::InProgress, ['in_progress_at' => now()]);
    }

    public function markAsReady(): void
    {
        $this->transitionTo(OrderStatus::Ready, ['ready_at' => now()]);
    }

    public function markAsCompleted(): void
    {
        $this->transitionTo(OrderStatus::Completed, ['completed_at' => now()]);
    }

    public function markAsCancelled(): void
    {
        $this->transitionTo(OrderStatus::Cancelled, ['cancelled_at' => now()]);
    }

    public function markAsRefunded(): void
    {
        $this->transitionTo(OrderStatus::Refunded);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Accepted->value,
            OrderStatus::InProgress->value,
            OrderStatus::Ready->value,
        ]);
    }

    // 決済直後の注文を KDS で取りこぼさないよう、通常のアクティブ表示に加えて Paid も許容する。
    public function scopeKdsVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Paid->value,
            OrderStatus::Accepted->value,
            OrderStatus::InProgress->value,
            OrderStatus::Ready->value,
        ]);
    }

    public function scopeForBusinessDate(Builder $query, string|Carbon $date): Builder
    {
        return $query->where('business_date', $date);
    }

    public function scopeWithStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    // 顧客は複数テナントで注文できるため、マイページ系の取得だけは TenantScope を外す。
    public function scopeForCustomerAcrossTenants(Builder $query, int $userId): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('user_id', $userId);
    }

    public function scopeWithCustomerList(Builder $query): Builder
    {
        return $query->with(['tenant:id,name']);
    }

    public function scopeWithCustomerDetail(Builder $query): Builder
    {
        return $query->with(['tenant:id,name,address', 'items.options', 'payment']);
    }

    public function scopeWithKdsDetails(Builder $query): Builder
    {
        return $query->with(['items.menuItem', 'items.options.option']);
    }

    // アラート判定では option 明細まで不要なので、一覧用より軽いロードで済ませる。
    public function scopeWithKdsAlert(Builder $query): Builder
    {
        return $query->with(['items.menuItem', 'user']);
    }

    public function loadCustomerDetail(): self
    {
        return $this->load(['tenant:id,name,slug,address', 'items.options', 'payment']);
    }

    public function loadKdsDetails(): self
    {
        return $this->load(['items.menuItem', 'items.options.option']);
    }
}

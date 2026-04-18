<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * @property PaymentStatus $status
 * @property PaymentMethod $method
 * @property Order $order
 */
class Payment extends Model
{
    use BelongsToTenant, HasFactory;

    // fincode関連フィールド（fincode_id, fincode_access_id, tds_trans_result,
    // tds_challenge_url, fincode_customer_id, fincode_card_id）はMass Assignment攻撃を
    // 防止するため意図的に除外し、Service層で直接プロパティ代入する
    // status, amount も決済の整合性を守るため$fillableから除外し、Service層で直接代入する
    protected $fillable = [
        'order_id',
        'tenant_id',
        'provider',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'amount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // 決済待ち状態かどうか
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    // 処理中状態かどうか
    public function isProcessing(): bool
    {
        return $this->status === PaymentStatus::Processing;
    }

    // 完了状態かどうか
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::Completed;
    }

    // 失敗状態かどうか
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    // 成功扱い（完了または処理中）かどうか
    public function isSuccessful(): bool
    {
        return $this->isCompleted() || $this->isProcessing();
    }

    // 指定したステータスへ遷移する
    public function transitionTo(PaymentStatus $newStatus): void
    {
        DB::transaction(function () use ($newStatus) {
            $fresh = static::lockForUpdate()->findOrFail($this->id);

            if ($fresh->status === $newStatus) {
                $this->setRawAttributes($fresh->getAttributes(), true);

                return;
            }

            if (! $fresh->status->canTransitionTo($newStatus)) {
                throw new InvalidArgumentException(
                    "Cannot transition from {$fresh->status->value} to {$newStatus->value}"
                );
            }

            $fresh->status = $newStatus;
            $fresh->save();
            $this->setRawAttributes($fresh->getAttributes(), true);
        });
    }

    // 処理中としてマークする
    public function markAsProcessing(): void
    {
        $this->transitionTo(PaymentStatus::Processing);
    }

    // 完了としてマークする
    public function markAsCompleted(): void
    {
        $this->transitionTo(PaymentStatus::Completed);
    }

    // 失敗としてマークする
    public function markAsFailed(): void
    {
        $this->transitionTo(PaymentStatus::Failed);
    }

    // 3DSセキュア関連メソッド

    // 3DSチャレンジが必要かどうか
    public function usesSavedCard(): bool
    {
        return $this->fincode_customer_id !== null && $this->fincode_card_id !== null;
    }

    public function requires3dsChallenge(): bool
    {
        return $this->tds_trans_result === 'C';
    }

    // 3DS認証済みかどうか
    public function is3dsAuthenticated(): bool
    {
        return in_array($this->tds_trans_result, ['Y', 'A'], true);
    }

    // 認証中としてマークする（3DS処理中）
    public function markAsAuthenticating(): void
    {
        $this->transitionTo(PaymentStatus::Processing);
    }
}

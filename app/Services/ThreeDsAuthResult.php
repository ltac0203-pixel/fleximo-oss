<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;

class ThreeDsAuthResult
{
    public const STATUS_AUTHENTICATED = 'authenticated';

    public const STATUS_REQUIRES_CHALLENGE = 'requires_challenge';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly Payment $payment,
        public readonly string $status,
        public readonly ?string $challengeUrl = null,
    ) {}

    // 認証成功の結果を生成する
    public static function authenticated(Payment $payment): self
    {
        return new self(
            payment: $payment,
            status: self::STATUS_AUTHENTICATED,
            challengeUrl: null,
        );
    }

    // チャレンジ必要の結果を生成する
    public static function requiresChallenge(Payment $payment, string $challengeUrl): self
    {
        return new self(
            payment: $payment,
            status: self::STATUS_REQUIRES_CHALLENGE,
            challengeUrl: $challengeUrl,
        );
    }

    // 認証失敗の結果を生成する
    public static function failed(Payment $payment): self
    {
        return new self(
            payment: $payment,
            status: self::STATUS_FAILED,
            challengeUrl: null,
        );
    }

    // リダイレクトが必要かどうか
    public function requiresRedirect(): bool
    {
        return $this->status === self::STATUS_REQUIRES_CHALLENGE && $this->challengeUrl !== null;
    }

    // 認証済みかどうか
    public function isAuthenticated(): bool
    {
        return $this->status === self::STATUS_AUTHENTICATED;
    }

    // 認証失敗かどうか
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    // 配列に変換する（APIレスポンス用）
    public function toArray(): array
    {
        $result = [
            'payment_id' => $this->payment->id,
            'status' => $this->status,
            'requires_3ds_redirect' => $this->requiresRedirect(),
        ];

        if ($this->requiresRedirect()) {
            $result['redirect_url'] = $this->challengeUrl;
        }

        return $result;
    }
}

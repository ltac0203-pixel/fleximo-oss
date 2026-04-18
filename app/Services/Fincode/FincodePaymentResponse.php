<?php

declare(strict_types=1);

namespace App\Services\Fincode;

use App\Enums\PaymentStatus;

class FincodePaymentResponse
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $accessId,
        public readonly ?string $status,
        public readonly ?int $amount,
        public readonly ?string $payType,
        public readonly ?string $linkUrl,
        public readonly ?string $clientField1,
        public readonly ?string $errorCode,
        public readonly array $rawResponse,
        public readonly ?string $tds2TransResult = null,
        public readonly ?string $challengeUrl = null,
        public readonly ?string $acsUrl = null,
    ) {}

    // 配列からインスタンスを生成する
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            accessId: $data['access_id'] ?? null,
            status: $data['status'] ?? null,
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
            payType: $data['pay_type'] ?? null,
            linkUrl: $data['link_url'] ?? $data['code_url'] ?? null,
            clientField1: $data['client_field_1'] ?? null,
            errorCode: $data['error_code'] ?? null,
            rawResponse: $data,
            tds2TransResult: $data['tds2_trans_result'] ?? null,
            challengeUrl: $data['challenge_url'] ?? null,
            acsUrl: $data['redirect_url'] ?? $data['acs_url'] ?? null,
        );
    }

    // 成功レスポンスかどうかを判定する
    public function isSuccess(): bool
    {
        return $this->errorCode === null;
    }

    // 決済がキャプチャ済み（売上確定済み）かどうかを判定する
    public function isCaptured(): bool
    {
        return $this->status === 'CAPTURED';
    }

    // リダイレクトが必要かどうかを判定する（PayPay等）
    public function requiresRedirect(): bool
    {
        return $this->linkUrl !== null;
    }

    // 3DSチャレンジが必要かどうか
    public function requires3dsChallenge(): bool
    {
        return $this->tds2TransResult === 'C';
    }

    // 3DS認証済みかどうか（Y: 認証成功, A: 試行認証成功）
    public function is3dsAuthenticated(): bool
    {
        return in_array($this->tds2TransResult, ['Y', 'A'], true);
    }

    // 3DS認証が失敗したかどうか
    public function is3dsAuthenticationFailed(): bool
    {
        return in_array($this->tds2TransResult, ['N', 'U', 'R'], true);
    }

    // fincodeステータスをPaymentStatusに変換する
    public function toPaymentStatus(): PaymentStatus
    {
        return match ($this->status) {
            'UNPROCESSED' => PaymentStatus::Pending,
            'AWAITING_AUTHENTICATION' => PaymentStatus::Processing,
            'AUTHORIZED' => PaymentStatus::Processing,
            'CAPTURED' => PaymentStatus::Completed,
            'CANCELED' => PaymentStatus::Failed,
            'ERROR' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    // 決済が未完了で処理中または待機中かどうかを判定する
    public function isPendingOrProcessing(): bool
    {
        $status = $this->toPaymentStatus();

        return in_array($status, [PaymentStatus::Pending, PaymentStatus::Processing], true);
    }
}

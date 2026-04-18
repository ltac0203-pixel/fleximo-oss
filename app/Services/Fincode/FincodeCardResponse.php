<?php

declare(strict_types=1);

namespace App\Services\Fincode;

class FincodeCardResponse
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $customerId,
        public readonly ?string $cardNo,
        public readonly ?string $brand,
        public readonly ?string $expire,
        public readonly ?bool $defaultFlag,
        public readonly ?string $errorCode,
        public readonly array $rawResponse,
    ) {}

    // 配列からインスタンスを生成する
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            customerId: $data['customer_id'] ?? null,
            cardNo: $data['card_no'] ?? null,
            brand: $data['brand'] ?? null,
            expire: $data['expire'] ?? null,
            defaultFlag: isset($data['default_flag']) ? (bool) $data['default_flag'] : null,
            errorCode: $data['error_code'] ?? null,
            rawResponse: $data,
        );
    }

    // 成功レスポンスかどうかを判定する
    public function isSuccess(): bool
    {
        return $this->errorCode === null && $this->id !== null;
    }

    public function getCardNoDisplay(): string
    {
        return $this->cardNo ?? '';
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Fincode;

class FincodeCustomerResponse
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $errorCode,
        public readonly array $rawResponse,
    ) {}

    // 配列からインスタンスを生成する
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            errorCode: $data['error_code'] ?? null,
            rawResponse: $data,
        );
    }

    // 成功レスポンスかどうかを判定する
    public function isSuccess(): bool
    {
        return $this->errorCode === null && $this->id !== null;
    }
}

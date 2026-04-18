<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Services\ThreeDsAuthResult;

final class ThreeDsWebCallbackOutcome
{
    private function __construct(
        private readonly ?ThreeDsAuthResult $result,
        private readonly ?string $errorKey,
    ) {}

    public static function success(ThreeDsAuthResult $result): self
    {
        return new self($result, null);
    }

    public static function failure(string $errorKey): self
    {
        return new self(null, $errorKey);
    }

    public function hasError(): bool
    {
        return $this->errorKey !== null;
    }

    public function errorKey(): ?string
    {
        return $this->errorKey;
    }

    public function result(): ?ThreeDsAuthResult
    {
        return $this->result;
    }
}

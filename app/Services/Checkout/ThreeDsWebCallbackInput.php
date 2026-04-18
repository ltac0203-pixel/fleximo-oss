<?php

declare(strict_types=1);

namespace App\Services\Checkout;

final class ThreeDsWebCallbackInput
{
    public function __construct(
        public readonly string $param,
        public readonly ?string $event = null,
    ) {}
}

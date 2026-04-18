<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\TenantServiceProvider;

return [
    AppServiceProvider::class,
    TenantServiceProvider::class,
];

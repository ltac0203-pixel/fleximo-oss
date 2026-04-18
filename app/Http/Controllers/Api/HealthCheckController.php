<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $healthy = $this->checkDatabase() && $this->checkCache();

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health_check_'.bin2hex(random_bytes(4));
            Cache::put($key, true, 5);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value === true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

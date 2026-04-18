<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotencyKey
{
    private const TTL_MINUTES = 30;

    private const PRIMARY_HEADER = 'Idempotency-Key';

    // 受信したリクエストを処理する。
    // @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->getIdempotencyKey($request);
        if ($key === null) {
            return response()->json([
                'message' => self::PRIMARY_HEADER.' header is required.',
            ], 422);
        }

        $key = trim($key);
        if ($key === '') {
            return response()->json([
                'message' => self::PRIMARY_HEADER.' header is required.',
            ], 400);
        }

        if (! $this->isValidUuidV4($key)) {
            return response()->json([
                'message' => self::PRIMARY_HEADER.' must be a UUIDv4 string.',
            ], 400);
        }

        $routeName = $request->route()?->getName() ?? $request->path();
        $userId = $request->user()?->id;
        $requestHash = $this->hashRequest($request);

        $record = $this->getOrCreateRecord(
            $key,
            $userId,
            $routeName,
            $request->method(),
            $requestHash
        );

        if (! $record->wasRecentlyCreated) {
            if ($record->request_hash !== $requestHash) {
                return response()->json([
                    'message' => 'Idempotency key already used with a different request.',
                ], 409);
            }

            if ($record->response_status !== null) {
                return response($record->response_body ?? '', $record->response_status)
                    ->header('Content-Type', 'application/json');
            }

            return response()->json([
                'message' => 'Request with this '.self::PRIMARY_HEADER.' is currently being processed.',
            ], 409);
        }

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $record->delete();
            throw $exception;
        }

        $this->persistResponse($record, $response);

        return $response;
    }

    private function getIdempotencyKey(Request $request): ?string
    {
        return $request->header(self::PRIMARY_HEADER);
    }

    private function isValidUuidV4(string $key): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $key
        ) === 1;
    }

    private function hashRequest(Request $request): string
    {
        $content = $request->getContent() ?? '';
        $query = $request->getQueryString() ?? '';
        $base = implode('|', [$request->method(), $request->path(), $query, $content]);

        return hash('sha256', $base);
    }

    private function getOrCreateRecord(
        string $key,
        ?int $userId,
        string $routeName,
        string $method,
        string $requestHash
    ): IdempotencyKey {
        $now = now();

        try {
            return DB::transaction(function () use ($key, $userId, $routeName, $method, $requestHash, $now) {
                IdempotencyKey::where('key', $key)
                    ->where('user_id', $userId)
                    ->where('route_name', $routeName)
                    ->where('expires_at', '<', $now)
                    ->delete();

                $existing = IdempotencyKey::where('key', $key)
                    ->where('user_id', $userId)
                    ->where('route_name', $routeName)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                return IdempotencyKey::create([
                    'user_id' => $userId,
                    'key' => $key,
                    'route_name' => $routeName,
                    'request_method' => $method,
                    'request_hash' => $requestHash,
                    'expires_at' => $now->copy()->addMinutes(self::TTL_MINUTES),
                ]);
            });
        } catch (QueryException $exception) {
            $existing = IdempotencyKey::where('key', $key)
                ->where('user_id', $userId)
                ->where('route_name', $routeName)
                ->first();

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function persistResponse(IdempotencyKey $record, Response $response): void
    {
        if ($response->getStatusCode() >= 500) {
            $record->delete();

            return;
        }

        $content = $response->getContent();
        $record->response_status = $response->getStatusCode();
        $record->response_body = $content === false || $content === null ? '' : $content;
        $record->save();
    }
}

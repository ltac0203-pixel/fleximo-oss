<?php

declare(strict_types=1);

namespace App\Services\Fincode;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FincodeRequestExecutor
{
    public function __construct(private FincodeLogSanitizer $logSanitizer) {}

    public function execute(
        string $method,
        string $url,
        string $endpoint,
        array $headers,
        array $data = []
    ): array {
        $timeout = (int) config('fincode.timeout', 30);
        $maxAttempts = (int) config('fincode.retry_times', 2);
        $baseSleepMs = (int) config('fincode.retry_sleep_ms', 500);

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->sendHttpRequest($method, $url, $headers, $data, $timeout);
                $responseData = $this->parseAndLogResponse($method, $endpoint, $response);

                $serverException = $this->handleErrorResponse(
                    $method,
                    $endpoint,
                    $response,
                    $responseData,
                    $attempt,
                    $maxAttempts
                );

                if ($serverException !== null) {
                    $lastException = $serverException;

                    if ($attempt < $maxAttempts) {
                        $this->sleepWithExponentialBackoff($baseSleepMs, $attempt);

                        continue;
                    }

                    throw $serverException;
                }

                return $responseData;
            } catch (FincodeApiException $e) {
                throw $e;
            } catch (ConnectionException $e) {
                $lastException = $e;

                if ($this->handleConnectionException(
                    $e,
                    $method,
                    $endpoint,
                    $attempt,
                    $maxAttempts,
                    $baseSleepMs
                )) {
                    continue;
                }
            } catch (RequestException $e) {
                $this->handleRequestException($e, $method, $endpoint);
            } catch (Exception $e) {
                $this->handleUnexpectedException($e, $method, $endpoint);
            }
        }

        // ここに到達することはないが、型安全のため
        throw $lastException ?? new FincodeApiException(null, [], '決済処理に失敗しました。');
    }

    private function sendHttpRequest(
        string $method,
        string $url,
        array $headers,
        array $data,
        int $timeout
    ): Response {
        $http = Http::withHeaders($headers)->timeout($timeout);

        return match (strtoupper($method)) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url),
            default => throw new FincodeApiException(null, [], "Unsupported HTTP method: {$method}"),
        };
    }

    private function parseAndLogResponse(string $method, string $endpoint, Response $response): array
    {
        $responseData = $response->json() ?? [];

        Log::info('fincode API response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'response' => $this->logSanitizer->sanitize($responseData),
        ]);

        return $responseData;
    }

    private function handleErrorResponse(
        string $method,
        string $endpoint,
        Response $response,
        array $responseData,
        int $attempt,
        int $maxAttempts
    ): ?FincodeApiException {
        // 5xx は fincode サーバー側の一時障害とみなしリトライ可能。冪等性を保証するため冪等キーを付与して再送する
        if ($response->status() >= 500) {
            $errorCode = $this->extractErrorCode($responseData);

            Log::warning('fincode API 5xx error, retrying', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
            ]);

            return new FincodeApiException(
                $errorCode,
                $responseData,
                '決済処理に失敗しました。',
                $response->status()
            );
        }

        // 4xx はリクエスト側の問題（バリデーションエラー等）でリトライしても解決しないため即座に例外を投げる
        if (! $response->successful()) {
            $errorCode = $this->extractErrorCode($responseData);
            $errorMessage = $this->extractErrorMessage($responseData);

            Log::warning('fincode API error response', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            throw new FincodeApiException(
                $errorCode,
                $responseData,
                '決済処理に失敗しました。',
                $response->status()
            );
        }

        return null;
    }

    private function handleConnectionException(
        ConnectionException $e,
        string $method,
        string $endpoint,
        int $attempt,
        int $maxAttempts,
        int $baseSleepMs
    ): bool {
        Log::warning('fincode API connection error, retrying', [
            'method' => $method,
            'endpoint' => $endpoint,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'error' => $e->getMessage(),
        ]);

        if ($attempt < $maxAttempts) {
            $this->sleepWithExponentialBackoff($baseSleepMs, $attempt);

            return true;
        }

        Log::error('fincode API connection failed after retries', [
            'method' => $method,
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
        ]);

        throw new FincodeApiException(
            'E01100002',
            [],
            '決済APIへの接続に失敗しました。',
            0,
            $e
        );
    }

    private function handleRequestException(RequestException $e, string $method, string $endpoint): void
    {
        Log::error('fincode API request error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
        ]);

        throw new FincodeApiException(
            null,
            [],
            '決済処理に失敗しました。',
            0,
            $e
        );
    }

    private function handleUnexpectedException(Exception $e, string $method, string $endpoint): void
    {
        Log::error('fincode API unexpected error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
        ]);

        throw new FincodeApiException(
            null,
            [],
            '決済処理中に予期しないエラーが発生しました。',
            0,
            $e
        );
    }

    private function extractErrorCode(array $responseData): ?string
    {
        return $responseData['errors'][0]['error_code'] ?? ($responseData['error_code'] ?? null);
    }

    private function extractErrorMessage(array $responseData): string
    {
        return $responseData['errors'][0]['error_message'] ?? ($responseData['message'] ?? 'Unknown error');
    }

    // リトライが同時に集中しないよう、ランダムなジッターを加えた指数バックオフで分散させる
    private function sleepWithExponentialBackoff(int $baseSleepMs, int $attempt): void
    {
        $sleepMs = $baseSleepMs * (2 ** ($attempt - 1));
        $jitter = (int) ($sleepMs * 0.25 * (mt_rand() / mt_getrandmax()));
        usleep(($sleepMs + $jitter) * 1000);
    }
}

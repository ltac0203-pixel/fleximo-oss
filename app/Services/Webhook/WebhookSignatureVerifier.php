<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

// Webhook 署名検証サービス
class WebhookSignatureVerifier
{
    public function __construct(
        private readonly string $secret
    ) {}

    // Webhook ペイロードのタイムスタンプを検証する（リプレイ攻撃防止）
    public function verifyTimestamp(string $payload, int $toleranceSeconds = 300): bool
    {
        $data = json_decode($payload, true);

        if (! is_array($data) || empty($data['created'])) {
            return false;
        }

        try {
            $created = Carbon::parse($data['created']);
        } catch (\Exception) {
            return false;
        }

        return abs(Carbon::now()->diffInSeconds($created)) <= $toleranceSeconds;
    }

    // fincode からの Webhook 署名を検証する
    public function verify(Request $request, string $signatureHeader = 'Fincode-Signature'): bool
    {
        $signature = $request->header($signatureHeader);

        if (empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = $this->computeSignature($payload);

        return hash_equals($expectedSignature, $signature);
    }

    // HMAC-SHA256 署名を計算する
    public function computeSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    // シークレットが設定されているか確認
    public function hasSecret(): bool
    {
        return ! empty($this->secret);
    }
}

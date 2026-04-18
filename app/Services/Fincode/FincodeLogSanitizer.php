<?php

declare(strict_types=1);

namespace App\Services\Fincode;

class FincodeLogSanitizer
{
    private const LOG_MASKED_VALUE = '***MASKED***';

    private const SENSITIVE_LOG_FIELDS = [
        // カード情報・トークン（明示的ブロック）
        'card_no',
        'card_number',
        'token',
        'access_id',
        'access_pass',
        // URL系（リダイレクト先・認証URL）
        'link_url',
        'code_url',
        'tds2_ret_url',
        'return_url',
        'challenge_url',
        'redirect_url',
        'acs_url',
        // カード関連メタ
        'customer_id',
        'brand',
        'expire',
        'default_flag',
    ];

    private const ALLOWED_LOG_FIELDS = [
        // === 共通フィールド ===
        'id',
        'error_code',

        // === Payment レスポンス ===
        // 基本情報
        'status',
        'pay_type',
        'job_code',
        'amount',
        'tax',
        'total_amount',
        'client_field_1',      // order_id
        'client_field_2',
        'client_field_3',

        // 決済詳細
        'method',
        'order_description',

        // 3DS関連
        'tds_type',
        'tds2_type',
        'tds2_trans_result',   // 3DS認証結果

        // === リスト系レスポンス ===
        'list',
        'has_more',
        'count',
        'total_count',

        // === Webhook / その他 ===
        'event',
        'created',
        'updated',
    ];

    public function sanitize(array $data): array
    {
        return $this->filterByWhitelist($data);
    }

    private function filterByWhitelist(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array($key, self::SENSITIVE_LOG_FIELDS, true)) {
                $sanitized[$key] = self::LOG_MASKED_VALUE;

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->filterByWhitelist($value);

                continue;
            }

            if (in_array($key, self::ALLOWED_LOG_FIELDS, true)) {
                $sanitized[$key] = $value;

                continue;
            }

            $sanitized[$key] = self::LOG_MASKED_VALUE;
        }

        return $sanitized;
    }
}

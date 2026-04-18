<?php

declare(strict_types=1);

namespace App\Support\Sentry;

use Sentry\Event;

final class SentryBeforeSend
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'card_number',
        'cvv',
        'cvc',
        'credit_card',
        'api_key',
        'authorization',
    ];

    public static function handle(Event $event): Event
    {
        $request = $event->getRequest();

        if (isset($request['data']) && is_array($request['data'])) {
            $request['data'] = self::sanitize($request['data']);
            $event->setRequest($request);
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (in_array($key, self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}

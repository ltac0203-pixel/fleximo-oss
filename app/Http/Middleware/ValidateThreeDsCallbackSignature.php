<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

class ValidateThreeDsCallbackSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $normalizedQueryString = $this->normalizeQueryString((string) $request->server->get('QUERY_STRING', ''));
        if ($normalizedQueryString !== (string) $request->server->get('QUERY_STRING', '')) {
            $request->server->set('QUERY_STRING', $normalizedQueryString);

            $normalizedQuery = [];
            parse_str($normalizedQueryString, $normalizedQuery);
            $request->query->replace($normalizedQuery);
        }

        // 既存互換: 追加クエリを含めて署名されたURLは標準検証で通す。
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        // 3DS復帰時にfincodeが付加するクエリパラメータのホワイトリスト
        $allowedExtraParameters = ['param', 'event', 'MD', 'PaRes'];

        // ホワイトリスト外の不明なパラメータが含まれる場合は拒否する
        $extraKeys = array_diff(
            array_keys($request->query()),
            ['signature', 'expires'],
            $allowedExtraParameters
        );

        if (count($extraKeys) > 0) {
            throw new InvalidSignatureException;
        }

        // ホワイトリスト内のパラメータを無視して署名を再検証する
        $ignoredParameters = array_values(array_intersect(
            array_keys($request->query()),
            $allowedExtraParameters
        ));

        if ($request->hasValidSignatureWhileIgnoring($ignoredParameters)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    private function normalizeQueryString(string $queryString): string
    {
        if ($queryString === '' || ! str_contains($queryString, '?')) {
            return $queryString;
        }

        $segments = explode('&', $queryString);
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if (! str_contains($segment, '?')) {
                if ($segment !== '') {
                    $normalizedSegments[] = $segment;
                }

                continue;
            }

            [$firstSegment, $rest] = explode('?', $segment, 2);
            if ($firstSegment !== '') {
                $normalizedSegments[] = $firstSegment;
            }

            if ($rest === '') {
                continue;
            }

            $rest = str_replace('?', '&', $rest);
            foreach (explode('&', $rest) as $restSegment) {
                if ($restSegment !== '') {
                    $normalizedSegments[] = $restSegment;
                }
            }
        }

        return implode('&', $normalizedSegments);
    }
}

<?php

namespace App\Support;

class ListReturn
{
    public static function back(string $fallback): string
    {
        $url = request('return');

        return self::isSafe($url) ? $url : $fallback;
    }

    public static function route(string $name, mixed $params = [], bool $keep = false): string
    {
        $params = is_array($params) ? $params : [$params];

        if ($keep) {
            if (self::isSafe(request('return'))) {
                $params['return'] = request('return');
            }
        } else {
            $params['return'] = request()->fullUrl();
        }

        return route($name, $params);
    }

    public static function isSafe(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (str_starts_with($url, '//') || str_contains($url, "\n") || str_contains($url, "\r")) {
            return false;
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        if (! isset($parsed['host'])) {
            return str_starts_with($url, '/');
        }

        return strcasecmp($parsed['host'], request()->getHost()) === 0;
    }
}

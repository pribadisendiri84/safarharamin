<?php

namespace App\Support;

class AdminListReturn
{
    public static function current(): string
    {
        return request()->fullUrl();
    }

    public static function resolve(?string $return, string $fallbackRoute = 'admin.packages.index', array $fallbackParams = []): string
    {
        if (! is_string($return) || trim($return) === '') {
            return route($fallbackRoute, $fallbackParams);
        }

        $return = trim($return);
        $path = parse_url($return, PHP_URL_PATH) ?: '';
        $indexPath = parse_url(route('admin.packages.index'), PHP_URL_PATH) ?: '';

        if ($path !== $indexPath) {
            return route($fallbackRoute, $fallbackParams);
        }

        $host = parse_url($return, PHP_URL_HOST);
        if ($host !== null && $host !== request()->getHost()) {
            return route($fallbackRoute, $fallbackParams);
        }

        return str_starts_with($return, 'http') ? $return : url($return);
    }
}

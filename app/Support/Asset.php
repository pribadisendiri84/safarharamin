<?php

namespace App\Support;

class Asset
{
    /**
     * URL aset publik dengan penanda versi agar browser tidak menyajikan file lama.
     */
    public static function url(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? (string) filemtime($file) : null;

        return asset($path).($version ? '?v='.$version : '');
    }
}

<?php

namespace App\Support;

class HomeDisplay
{
    public const HOME_LIMIT = 24;

    public static function packageLimit(): int
    {
        return self::HOME_LIMIT;
    }

    public static function galleryLimit(): int
    {
        return self::HOME_LIMIT;
    }
}

<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCaptcha
{
    public const HASH_KEY = 'admin_captcha_hash';

    public const EXPIRES_KEY = 'admin_captcha_expires_at';

    public function image(Request $request): string
    {
        $answer = Str::upper(Str::random(6));
        $request->session()->put([
            self::HASH_KEY => hash('sha256', $answer),
            self::EXPIRES_KEY => now()->addSeconds((int) config('admin-auth.captcha_ttl_seconds', 300))->timestamp,
        ]);

        $image = imagecreatetruecolor(220, 72);
        if ($image === false) {
            throw new \RuntimeException('Gagal membuat gambar CAPTCHA.');
        }

        $background = imagecolorallocate($image, 244, 247, 252);
        $ink = imagecolorallocate($image, 1, 49, 128);
        $noise = imagecolorallocate($image, 152, 166, 190);
        imagefill($image, 0, 0, $background);

        for ($i = 0; $i < 9; $i++) {
            imageline(
                $image,
                random_int(0, 220),
                random_int(0, 72),
                random_int(0, 220),
                random_int(0, 72),
                $noise,
            );
        }

        for ($i = 0; $i < 120; $i++) {
            imagesetpixel($image, random_int(0, 219), random_int(0, 71), $noise);
        }

        foreach (str_split($answer) as $index => $character) {
            imagestring(
                $image,
                5,
                22 + ($index * 31) + random_int(-2, 2),
                25 + random_int(-7, 7),
                $character,
                $ink,
            );
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    public function verify(Request $request, string $answer): bool
    {
        $hash = (string) $request->session()->pull(self::HASH_KEY, '');
        $expiresAt = (int) $request->session()->pull(self::EXPIRES_KEY, 0);

        if ($hash === '' || $expiresAt < now()->timestamp) {
            return false;
        }

        return hash_equals($hash, hash('sha256', Str::upper(trim($answer))));
    }
}

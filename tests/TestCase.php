<?php

namespace Tests;

use App\Models\PackageKind;
use App\Support\AdminCaptcha;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function packageKindId(string $slug = 'arafah'): int
    {
        $id = PackageKind::query()->where('slug', $slug)->value('id');

        $this->assertNotNull($id, 'Master tipe paket "'.$slug.'" harus ter-seed.');

        return (int) $id;
    }

    /**
     * @return array<string, int|string>
     */
    protected function validCaptchaSession(string $answer = 'ABC123'): array
    {
        return [
            AdminCaptcha::HASH_KEY => hash('sha256', strtoupper($answer)),
            AdminCaptcha::EXPIRES_KEY => now()->addMinutes(5)->timestamp,
        ];
    }
}

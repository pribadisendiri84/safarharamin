<?php

namespace Tests\Unit;

use App\Services\PackageImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PackageImageStoreTest extends TestCase
{
    public function test_store_resizes_large_images(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required.');
        }

        Storage::fake('public');

        $store = new PackageImageStore;
        $path = $store->store(UploadedFile::fake()->image('flyer.jpg', 2400, 3200), 'Paket Besar');

        $storedPath = str_replace('/storage/', '', $path);
        $contents = Storage::disk('public')->get($storedPath);
        $info = getimagesizefromstring($contents);

        $this->assertNotFalse($info);
        $this->assertLessThanOrEqual(1200, $info[0]);
        $this->assertLessThanOrEqual(1700, $info[1]);
    }

    public function test_store_keeps_small_images_under_limit(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required.');
        }

        Storage::fake('public');

        $store = new PackageImageStore;
        $path = $store->store(UploadedFile::fake()->image('flyer.jpg', 800, 1100), 'Paket Kecil');

        $storedPath = str_replace('/storage/', '', $path);
        $contents = Storage::disk('public')->get($storedPath);
        $info = getimagesizefromstring($contents);

        $this->assertNotFalse($info);
        $this->assertSame(800, $info[0]);
        $this->assertSame(1100, $info[1]);
    }
}

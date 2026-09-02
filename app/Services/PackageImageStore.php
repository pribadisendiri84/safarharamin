<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PackageImageStore
{
    public const PREFIX = '/storage/packages/';

    private const MAX_WIDTH = 1200;

    private const MAX_HEIGHT = 1700;

    private const JPEG_QUALITY = 82;

    public function store(UploadedFile $file, string $title, string $folder = 'packages'): string
    {
        Storage::disk('public')->makeDirectory($folder);

        $optimized = $this->optimize($file);
        $filename = $this->filename($title, $file, $optimized['extension']);
        Storage::disk('public')->put($folder.'/'.$filename, $optimized['contents']);

        return '/storage/'.$folder.'/'.$filename;
    }

    public function filename(string $title, UploadedFile $file, ?string $extension = null): string
    {
        $slug = Str::slug($title) ?: 'paket';
        $slug = Str::limit($slug, 50, '');
        $uniq = Str::lower(Str::random(6));
        $ext = strtolower($extension ?? (string) $file->guessExtension());
        $ext = in_array($ext, ['jpeg', 'jpe'], true) ? 'jpg' : ($ext ?: 'jpg');

        return $slug.'-'.$uniq.'.'.$ext;
    }

    /**
     * @return array{contents: string, extension: string}
     */
    private function optimize(UploadedFile $file): array
    {
        if (! extension_loaded('gd')) {
            return [
                'contents' => (string) file_get_contents($file->getRealPath()),
                'extension' => $this->normalizedExtension($file),
            ];
        }

        $path = $file->getRealPath();
        if ($path === false) {
            return [
                'contents' => (string) file_get_contents($file->getPathname()),
                'extension' => $this->normalizedExtension($file),
            ];
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return [
                'contents' => (string) file_get_contents($path),
                'extension' => $this->normalizedExtension($file),
            ];
        }

        [$width, $height, $type] = $info;
        $source = $this->createImage($path, $type);
        if ($source === null) {
            return [
                'contents' => (string) file_get_contents($path),
                'extension' => $this->normalizedExtension($file),
            ];
        }

        [$targetWidth, $targetHeight] = $this->targetDimensions($width, $height);
        $canvas = $this->resizeCanvas($source, $width, $height, $targetWidth, $targetHeight);
        imagedestroy($source);

        $usePng = $type === IMAGETYPE_PNG && $this->hasTransparency($canvas, $targetWidth, $targetHeight);
        $extension = $usePng ? 'png' : 'jpg';

        ob_start();
        if ($usePng) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagepng($canvas, null, 8);
        } else {
            imagejpeg($canvas, null, self::JPEG_QUALITY);
        }
        $contents = (string) ob_get_clean();
        imagedestroy($canvas);

        return compact('contents', 'extension');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function targetDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            return [$width, $height];
        }

        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    private function createImage(string $path, int $type): ?\GdImage
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($path) ?: null,
            default => null,
        };
    }

    private function resizeCanvas(\GdImage $source, int $width, int $height, int $targetWidth, int $targetHeight): \GdImage
    {
        if ($targetWidth === $width && $targetHeight === $height) {
            $canvas = imagecreatetruecolor($width, $height);
            imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

            return $canvas;
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function hasTransparency(\GdImage $image, int $width, int $height): bool
    {
        for ($x = 0; $x < $width; $x += max(1, (int) ($width / 8))) {
            for ($y = 0; $y < $height; $y += max(1, (int) ($height / 8))) {
                $rgba = imagecolorat($image, $x, $y);
                if (($rgba & 0x7F000000) >> 24) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizedExtension(UploadedFile $file): string
    {
        $ext = strtolower((string) $file->guessExtension());

        return in_array($ext, ['jpeg', 'jpe'], true) ? 'jpg' : ($ext ?: 'jpg');
    }
}

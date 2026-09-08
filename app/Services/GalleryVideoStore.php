<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryVideoStore
{
    public const PREFIX = '/storage/gallery-videos/';

    public const MAX_BYTES = 52_428_800;

    public const MAX_MEGABYTES = 50;

    private const FOLDER = 'gallery-videos';

    public function store(UploadedFile $file, string $title): string
    {
        Storage::disk('public')->makeDirectory(self::FOLDER);

        $slug = Str::slug(Str::limit($title, 40, '')) ?: 'gallery-video';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        if (! in_array($extension, ['mp4', 'webm'], true)) {
            $extension = 'mp4';
        }

        $filename = $slug.'-'.Str::lower(Str::random(8)).'.'.$extension;
        Storage::disk('public')->putFileAs(self::FOLDER, $file, $filename);

        return self::PREFIX.$filename;
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, self::PREFIX)) {
            return;
        }

        $relative = ltrim(substr($path, strlen('/storage/')), '/');
        Storage::disk('public')->delete($relative);
    }
}

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HajiPageItineraryStore
{
    public const PREFIX = '/storage/haji-page-itineraries/';

    private const FOLDER = 'haji-page-itineraries';

    public function store(UploadedFile $file, string $label): string
    {
        Storage::disk('public')->makeDirectory(self::FOLDER);

        $slug = Str::slug(Str::limit($label, 40, '')) ?: 'itinerary';
        $filename = $slug.'-'.Str::lower(Str::random(8)).'.pdf';

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

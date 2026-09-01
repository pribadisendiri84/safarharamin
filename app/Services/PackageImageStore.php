<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PackageImageStore
{
    public const PREFIX = '/storage/packages/';

    public function store(UploadedFile $file, string $title, string $folder = 'packages'): string
    {
        Storage::disk('public')->makeDirectory($folder);

        $filename = $this->filename($title, $file);
        Storage::disk('public')->putFileAs($folder, $file, $filename);

        return '/storage/'.$folder.'/'.$filename;
    }

    public function filename(string $title, UploadedFile $file): string
    {
        $slug = Str::slug($title) ?: 'paket';
        $slug = Str::limit($slug, 50, '');
        $uniq = Str::lower(Str::random(6));
        $ext = strtolower((string) $file->guessExtension());
        $ext = in_array($ext, ['jpeg', 'jpe'], true) ? 'jpg' : ($ext ?: 'jpg');

        return $slug.'-'.$uniq.'.'.$ext;
    }
}

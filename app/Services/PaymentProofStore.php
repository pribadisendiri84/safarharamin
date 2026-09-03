<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentProofStore
{
    public const PREFIX = '/storage/payment-proofs/';

    private const FOLDER = 'payment-proofs';

    public function __construct(private PackageImageStore $images) {}

    public function store(UploadedFile $file, string $label): string
    {
        if ($this->isPdf($file)) {
            return $this->storePdf($file, $label);
        }

        return $this->images->store($file, $label, self::FOLDER);
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, self::PREFIX)) {
            return;
        }

        $relative = ltrim(substr($path, strlen('/storage/')), '/');
        Storage::disk('public')->delete($relative);
    }

    private function isPdf(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());

        return $mime === 'application/pdf' || $ext === 'pdf';
    }

    private function storePdf(UploadedFile $file, string $label): string
    {
        Storage::disk('public')->makeDirectory(self::FOLDER);

        $slug = Str::slug(Str::limit($label, 40, '')) ?: 'bukti';
        $filename = $slug.'-'.Str::lower(Str::random(8)).'.pdf';

        Storage::disk('public')->putFileAs(self::FOLDER, $file, $filename);

        return self::PREFIX.$filename;
    }
}

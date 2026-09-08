<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\GalleryVideoStore;
use App\Services\PackageImageStore;
use App\Support\YoutubeUrl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            GalleryItem::query()->with('creator')->orderBy('sort_order')->orderByDesc('id'),
            $request
        );

        $category = $request->string('category')->toString();
        if (array_key_exists($category, GalleryItem::categories())) {
            $query->category($category);
        }

        return view('admin.gallery.index', [
            'items' => $request->boolean('trashed') ? $query->paginate(24)->withQueryString() : $query->get(),
            'homeItems' => $request->boolean('trashed') ? collect() : GalleryItem::homeItemsForAdmin(),
            'category' => $category,
            ...$this->trashViewData(GalleryItem::class, $request),
        ]);
    }

    public function create()
    {
        return view('admin.gallery.form', ['item' => new GalleryItem]);
    }

    public function store(Request $request, PackageImageStore $images, GalleryVideoStore $videos)
    {
        GalleryItem::query()->create($this->validated($request, $images, $videos));

        return redirect()->route('admin.gallery.index')->with('ok', 'Item galeri ditambahkan.');
    }

    public function edit(GalleryItem $gallery)
    {
        return view('admin.gallery.form', ['item' => $gallery]);
    }

    public function update(Request $request, GalleryItem $gallery, PackageImageStore $images, GalleryVideoStore $videos)
    {
        $gallery->update($this->validated($request, $images, $videos, $gallery));

        return redirect()->route('admin.gallery.index')->with('ok', 'Item galeri diperbarui.');
    }

    public function destroy(GalleryItem $gallery, GalleryVideoStore $videos)
    {
        if ($gallery->isUploadedVideo()) {
            $videos->delete($gallery->video_path);
        }

        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('ok', 'Item galeri dihapus.');
    }

    public function restore(GalleryItem $gallery)
    {
        $gallery->restore();

        return redirect()->route('admin.gallery.index', ['trashed' => 1])->with('ok', 'Item galeri dipulihkan.');
    }

    public function toggleHome(Request $request, GalleryItem $gallery)
    {
        $show = $request->boolean('show_on_home');

        if ($show === (bool) $gallery->show_on_home) {
            return $this->galleryHomeResponse($gallery, '');
        }

        if ($show) {
            if (! GalleryItem::canAddToHome($gallery->id)) {
                return $this->galleryHomeRejected($gallery, 'Beranda galeri sudah penuh (maks. '.GalleryItem::homeLimit().'). Hapus centang foto lain dulu.');
            }

            $slot = GalleryItem::nextAvailableHomeSlot($gallery->id);
            $gallery->update([
                'show_on_home' => true,
                'home_sort' => $slot,
            ]);
            $message = 'Item ditampilkan di beranda (posisi '.$slot.').';
        } else {
            $gallery->update([
                'show_on_home' => false,
                'home_sort' => null,
            ]);
            $message = 'Item dihapus dari beranda.';
        }

        return $this->galleryHomeResponse($gallery->fresh(), $message);
    }

    private function galleryHomeResponse(GalleryItem $gallery, string $message)
    {
        $payload = [
            'ok' => true,
            'message' => $message,
            'id' => $gallery->id,
            'featured' => $gallery->show_on_home,
            'home_sort' => $gallery->home_sort,
        ];

        if ($gallery->show_on_home) {
            $payload['item'] = [
                'id' => $gallery->id,
                'title' => $gallery->title,
                'thumb' => $gallery->displayImage(),
            ];
        }

        if (request()->expectsJson()) {
            return response()->json($payload);
        }

        $redirect = redirect()->route('admin.gallery.index');

        return $message !== '' ? $redirect->with('ok', $message) : $redirect;
    }

    private function galleryHomeRejected(GalleryItem $gallery, string $message)
    {
        $payload = [
            'ok' => false,
            'message' => $message,
            'id' => $gallery->id,
            'featured' => $gallery->show_on_home,
            'home_sort' => $gallery->home_sort,
        ];

        if (request()->expectsJson()) {
            return response()->json($payload, 422);
        }

        return redirect()->route('admin.gallery.index')->with('err', $message);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:home,gallery'],
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:gallery_items,id'],
        ]);

        if ($data['type'] === 'home') {
            GalleryItem::applyHomeOrder(array_map('intval', $data['order']));
        } else {
            GalleryItem::applyGalleryOrder(array_map('intval', $data['order']));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @return array{title: string, caption: ?string, category: string, group_name: ?string, sort_order: int, show_on_home: bool, home_sort: ?int, image: string, video_url: ?string, video_path: ?string}
     */
    private function validated(
        Request $request,
        PackageImageStore $images,
        GalleryVideoStore $videos,
        ?GalleryItem $existingItem = null,
    ): array {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(GalleryItem::categories()))],
            'group_name' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'show_on_home' => ['nullable', 'boolean'],
            'media_type' => ['required', Rule::in(['photo', 'youtube', 'file'])],
            'image_url' => ['nullable', 'url', 'max:500'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:'.GalleryVideoStore::MAX_MEGABYTES * 1024],
            'delete_video_file' => ['nullable', 'boolean'],
        ], [
            'video_file.max' => 'Video maksimal '.GalleryVideoStore::MAX_MEGABYTES.' MB per upload. Kompres dulu jika lebih besar.',
            'video_file.mimetypes' => 'Format video harus MP4 atau WebM.',
        ]);

        $mediaType = $data['media_type'];
        $showOnHome = $request->boolean('show_on_home');
        $videoUrl = filled($data['video_url'] ?? null) ? trim($data['video_url']) : null;
        $videoPath = $existingItem?->video_path;
        $previousVideoPath = $existingItem?->video_path;

        if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
            if ($mediaType !== 'file') {
                throw ValidationException::withMessages([
                    'video_file' => 'Unggah video hanya untuk tipe Upload video.',
                ]);
            }

            if ($previousVideoPath) {
                $videos->delete($previousVideoPath);
            }

            $videoPath = $videos->store($request->file('video_file'), $data['title']);
            $videoUrl = null;
        } elseif ($mediaType === 'file') {
            if ($request->boolean('delete_video_file') && $previousVideoPath) {
                $videos->delete($previousVideoPath);
                $videoPath = null;
            }

            if (! $videoPath) {
                throw ValidationException::withMessages([
                    'video_file' => 'Unggah file video MP4/WebM (maks. '.GalleryVideoStore::MAX_MEGABYTES.' MB).',
                ]);
            }

            $videoUrl = null;
        } elseif ($mediaType === 'youtube') {
            if ($videoUrl === null) {
                throw ValidationException::withMessages([
                    'video_url' => 'Isi link video YouTube.',
                ]);
            }

            if (YoutubeUrl::extractId($videoUrl) === null) {
                throw ValidationException::withMessages([
                    'video_url' => 'URL YouTube tidak valid.',
                ]);
            }

            if ($previousVideoPath) {
                $videos->delete($previousVideoPath);
            }

            $videoPath = null;
        } else {
            if ($videoUrl !== null || $request->hasFile('video_file')) {
                throw ValidationException::withMessages([
                    'media_type' => 'Hapus link/upload video jika tipe media adalah Foto.',
                ]);
            }

            if ($previousVideoPath) {
                $videos->delete($previousVideoPath);
            }

            $videoUrl = null;
            $videoPath = null;
        }

        $image = $existingItem?->image;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $image = $images->store($request->file('photo'), $data['title'], 'gallery');
        } elseif (filled($data['image_url'] ?? null)) {
            $image = $data['image_url'];
        } elseif ($mediaType === 'youtube' && $videoUrl !== null && ! $image) {
            $image = YoutubeUrl::thumbnailUrl($videoUrl);
        }

        if ($mediaType === 'file' && ! $image) {
            throw ValidationException::withMessages([
                'photo' => 'Unggah thumbnail/poster untuk video (wajib).',
            ]);
        }

        if ($mediaType === 'photo' && ! $image) {
            throw ValidationException::withMessages([
                'photo' => 'Unggah foto atau isi URL gambar.',
            ]);
        }

        if (! $image) {
            throw ValidationException::withMessages([
                'photo' => 'Unggah foto/thumbnail atau isi URL gambar.',
            ]);
        }

        return [
            'title' => $data['title'],
            'caption' => $data['caption'] ?? null,
            'category' => $data['category'],
            'group_name' => filled($data['group_name'] ?? null) ? trim($data['group_name']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'show_on_home' => $showOnHome,
            'home_sort' => $this->resolveHomeSort($showOnHome, $existingItem),
            'image' => $image,
            'video_url' => $videoUrl,
            'video_path' => $videoPath,
        ];
    }

    private function resolveHomeSort(bool $showOnHome, ?GalleryItem $existing = null): ?int
    {
        if (! $showOnHome) {
            return null;
        }

        if ($existing?->show_on_home && ($existing->home_sort ?? 0) > 0) {
            return (int) $existing->home_sort;
        }

        if (! GalleryItem::canAddToHome($existing?->id)) {
            throw ValidationException::withMessages([
                'show_on_home' => 'Beranda galeri sudah penuh (maks. '.GalleryItem::homeLimit().'). Hapus centang foto lain dulu.',
            ]);
        }

        $slot = GalleryItem::nextAvailableHomeSlot($existing?->id);

        if ($slot === null) {
            throw ValidationException::withMessages([
                'show_on_home' => 'Beranda galeri sudah penuh (maks. '.GalleryItem::homeLimit().'). Hapus centang foto lain dulu.',
            ]);
        }

        return $slot;
    }
}

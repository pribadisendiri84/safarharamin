<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\PackageImageStore;
use Illuminate\Http\Request;
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

    public function store(Request $request, PackageImageStore $images)
    {
        GalleryItem::query()->create($this->validated($request, $images));

        return redirect()->route('admin.gallery.index')->with('ok', 'Foto galeri ditambahkan.');
    }

    public function edit(GalleryItem $gallery)
    {
        return view('admin.gallery.form', ['item' => $gallery]);
    }

    public function update(Request $request, GalleryItem $gallery, PackageImageStore $images)
    {
        $gallery->update($this->validated($request, $images, $gallery));

        return redirect()->route('admin.gallery.index')->with('ok', 'Foto galeri diperbarui.');
    }

    public function destroy(GalleryItem $gallery)
    {
        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('ok', 'Foto galeri dihapus.');
    }

    public function restore(GalleryItem $gallery)
    {
        $gallery->restore();

        return redirect()->route('admin.gallery.index', ['trashed' => 1])->with('ok', 'Foto galeri dipulihkan.');
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
            $message = 'Foto ditampilkan di beranda (posisi '.$slot.').';
        } else {
            $gallery->update([
                'show_on_home' => false,
                'home_sort' => null,
            ]);
            $message = 'Foto dihapus dari beranda.';
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
                'thumb' => $gallery->image,
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
     * @return array{title: string, caption: ?string, category: string, group_name: ?string, sort_order: int, show_on_home: bool, image: string}
     */
    private function validated(Request $request, PackageImageStore $images, ?GalleryItem $existingItem = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(GalleryItem::categories()))],
            'group_name' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'show_on_home' => ['nullable', 'boolean'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $showOnHome = $request->boolean('show_on_home');
        $image = $existingItem?->image;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $image = $images->store($request->file('photo'), $data['title'], 'gallery');
        } elseif (filled($data['image_url'] ?? null)) {
            $image = $data['image_url'];
        }

        if (! $image) {
            throw ValidationException::withMessages([
                'photo' => 'Unggah foto atau isi URL gambar.',
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

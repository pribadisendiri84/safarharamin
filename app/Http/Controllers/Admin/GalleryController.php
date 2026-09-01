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

        return view('admin.gallery.index', [
            'items' => $query->paginate(24)->withQueryString(),
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
        $gallery->update($this->validated($request, $images, $gallery->image));

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

    /**
     * @return array{title: string, caption: ?string, sort_order: int, image: string}
     */
    private function validated(Request $request, PackageImageStore $images, ?string $existing = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $image = $existing;
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
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'image' => $image,
        ];
    }
}

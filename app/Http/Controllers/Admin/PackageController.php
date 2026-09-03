<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Package;
use App\Services\PackageImageStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackageController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Package::query()
                ->with('creator')
                ->withSum(['inquiries as sold_pax' => fn ($q) => $q->where('status', Inquiry::STATUS_SOLD)], 'sold_pax')
                ->latest(),
            $request
        );

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if (in_array($request->input('featured'), ['0', '1'], true)) {
            $query->where('is_featured', $request->input('featured') === '1');
        }

        if (in_array($request->input('data_complete'), ['0', '1'], true)) {
            if ($request->input('data_complete') === '1') {
                $query->dataComplete();
            } else {
                $query->dataIncomplete();
            }
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where('title', 'like', '%'.$q.'%');
        }

        return view('admin.packages.index', [
            'packages' => $query->paginate(20)->withQueryString(),
            'homePackages' => $request->boolean('trashed') ? collect() : Package::homeItemsForAdmin(),
            ...$this->trashViewData(Package::class, $request),
        ]);
    }

    public function create()
    {
        return view('admin.packages.form', ['package' => new Package]);
    }

    public function store(Request $request, PackageImageStore $images)
    {
        $data = $this->validated($request);
        $data['slug'] = Package::uniqueSlug($data['title']);
        $data['images'] = $this->collectImages($request, $images, $data['title']);
        $this->assertFlyerForPublish($data['images'], $data['status']);
        $data['facilities'] = $this->lines($request->input('facilities_text'));
        $data['exclusions'] = $this->lines($request->input('exclusions_text'));

        Package::query()->create($data);

        return redirect()->route('admin.packages.index')->with('ok', 'Paket ditambahkan.');
    }

    public function edit(Package $package)
    {
        return view('admin.packages.form', ['package' => $package]);
    }

    public function update(Request $request, Package $package, PackageImageStore $images)
    {
        $data = $this->validated($request, $package);
        $data['images'] = $this->collectImages($request, $images, $data['title'], $package->images ?? []);
        $this->assertFlyerForPublish($data['images'], $data['status']);
        $data['facilities'] = $this->lines($request->input('facilities_text'));
        $data['exclusions'] = $this->lines($request->input('exclusions_text'));

        $package->update($data);

        return redirect()->route('admin.packages.index')->with('ok', 'Paket diperbarui.');
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->route('admin.packages.index')->with('ok', 'Paket dihapus.');
    }

    public function restore(Package $package)
    {
        $package->restore();

        return redirect()->route('admin.packages.index', ['trashed' => 1])->with('ok', 'Paket dipulihkan.');
    }

    public function duplicate(Package $package)
    {
        $copy = $package->replicate(['slug']);
        $copy->title = $package->title.' (salinan)';
        $copy->departure_date = null;
        $copy->images = [];
        $copy->status = 'draft';

        return view('admin.packages.form', [
            'package' => $copy,
            'isDuplicate' => true,
        ]);
    }

    public function toggleFeatured(Request $request, Package $package)
    {
        $show = $request->boolean('is_featured');

        if ($show === (bool) $package->is_featured) {
            return $this->packageFeaturedResponse($package, '');
        }

        if ($show) {
            if (! Package::canAddToHome($package->id)) {
                return $this->packageFeaturedRejected($package, 'Beranda paket sudah penuh (maks. '.Package::homeLimit().'). Hapus centang paket lain dulu.');
            }

            $slot = Package::nextAvailableHomeSlot($package->id);
            $package->update([
                'is_featured' => true,
                'home_sort' => $slot,
            ]);

            $message = 'Paket ditampilkan di beranda (posisi '.$slot.').';
        } else {
            $package->update([
                'is_featured' => false,
                'home_sort' => null,
            ]);
            $message = 'Paket dihapus dari beranda.';
        }

        return $this->packageFeaturedResponse($package->fresh(), $message);
    }

    public function updateStatus(Request $request, Package $package)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Package::STATUSES))],
        ]);

        if ($data['status'] === $package->status) {
            return $this->packageStatusResponse($package, '');
        }

        try {
            $this->assertFlyerForPublish($package->images ?? [], $data['status']);
        } catch (ValidationException $exception) {
            return $this->packageStatusRejected(
                $package,
                (string) ($exception->errors()['photos'][0] ?? 'Unggah flyer paket sebelum menayangkan.')
            );
        }

        $package->update(['status' => $data['status']]);

        return $this->packageStatusResponse($package->fresh(), 'Status paket diperbarui.');
    }

    private function packageStatusResponse(Package $package, string $message)
    {
        $payload = [
            'ok' => true,
            'message' => $message,
            'id' => $package->id,
            'status' => $package->status,
            'status_label' => Package::STATUSES[$package->status] ?? $package->status,
        ];

        if (request()->expectsJson()) {
            return response()->json($payload);
        }

        $redirect = redirect()->route('admin.packages.index');

        return $message !== '' ? $redirect->with('ok', $message) : $redirect;
    }

    private function packageStatusRejected(Package $package, string $message)
    {
        $payload = [
            'ok' => false,
            'message' => $message,
            'id' => $package->id,
            'status' => $package->status,
            'status_label' => Package::STATUSES[$package->status] ?? $package->status,
        ];

        if (request()->expectsJson()) {
            return response()->json($payload, 422);
        }

        return redirect()->route('admin.packages.index')->with('err', $message);
    }

    private function packageFeaturedResponse(Package $package, string $message)
    {
        $payload = [
            'ok' => true,
            'message' => $message,
            'id' => $package->id,
            'featured' => $package->is_featured,
            'home_sort' => $package->home_sort,
        ];

        if ($package->is_featured) {
            $payload['item'] = [
                'id' => $package->id,
                'title' => $package->title,
                'thumb' => $package->coverImage(),
                'meta' => $package->formattedStartingPrice(),
            ];
        }

        if (request()->expectsJson()) {
            return response()->json($payload);
        }

        $redirect = redirect()->route('admin.packages.index');

        return $message !== '' ? $redirect->with('ok', $message) : $redirect;
    }

    private function packageFeaturedRejected(Package $package, string $message)
    {
        $payload = [
            'ok' => false,
            'message' => $message,
            'id' => $package->id,
            'featured' => $package->is_featured,
            'home_sort' => $package->home_sort,
        ];

        if (request()->expectsJson()) {
            return response()->json($payload, 422);
        }

        return redirect()->route('admin.packages.index')->with('err', $message);
    }

    public function reorderHome(Request $request)
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:packages,id'],
        ]);

        Package::applyHomeOrder(array_map('intval', $data['order']));

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Package $existing = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in(array_keys(Package::TYPES))],
            'departure_city' => ['required', Rule::exists('cities', 'slug')->whereNull('deleted_at')],
            'departure_date' => ['nullable', 'date'],
            'duration_days' => ['required', 'integer', 'min:7', 'max:45'],
            'price_quad' => ['required', 'integer', 'min:1'],
            'price_triple' => ['required', 'integer', 'min:1'],
            'price_double' => ['required', 'integer', 'min:1'],
            'original_price' => ['nullable', 'integer', 'min:1'],
            'price_note' => ['nullable', 'string', 'max:180'],
            'hotel_makkah' => ['nullable', 'string', 'max:120'],
            'hotel_madinah' => ['nullable', 'string', 'max:120'],
            'hotel_stars' => ['required', 'integer', 'min:3', 'max:5'],
            'airline' => ['nullable', 'string', 'max:80'],
            'seats_total' => ['required', 'integer', 'min:1'],
            'seats_left' => ['required', 'integer', 'min:0'],
            'itinerary' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Package::STATUSES))],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'facilities_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
        ]);

        unset($data['facilities_text'], $data['exclusions_text'], $data['photos']);
        $data['price'] = $data['price_quad'];
        $data['room_type'] = 'quad';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_hot'] = $request->boolean('is_hot');
        $data['home_sort'] = $this->resolveHomeSort($data['is_featured'], $existing);

        return $data;
    }

    private function resolveHomeSort(bool $isFeatured, ?Package $existing = null): ?int
    {
        if (! $isFeatured) {
            return null;
        }

        if ($existing?->is_featured && ($existing->home_sort ?? 0) > 0) {
            return (int) $existing->home_sort;
        }

        if (! Package::canAddToHome($existing?->id)) {
            throw ValidationException::withMessages([
                'is_featured' => 'Beranda paket sudah penuh (maks. '.Package::homeLimit().'). Hapus centang paket lain dulu.',
            ]);
        }

        $slot = Package::nextAvailableHomeSlot($existing?->id);

        if ($slot === null) {
            throw ValidationException::withMessages([
                'is_featured' => 'Beranda paket sudah penuh (maks. '.Package::homeLimit().'). Hapus centang paket lain dulu.',
            ]);
        }

        return $slot;
    }

    /**
     * @param  list<string>  $existing
     * @return list<string>
     */
    private function collectImages(Request $request, PackageImageStore $store, string $title, array $existing = []): array
    {
        $urls = [];

        foreach ($request->file('photos', []) as $file) {
            if ($file && $file->isValid()) {
                $urls[] = $store->store($file, $title);
            }
        }

        return $urls !== [] ? $urls : $existing;
    }

    /**
     * @param  list<string>  $images
     */
    private function assertFlyerForPublish(array $images, string $status): void
    {
        if ($status !== 'published') {
            return;
        }

        if ($images === []) {
            throw ValidationException::withMessages([
                'photos' => 'Unggah flyer paket sebelum menayangkan.',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function lines(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}

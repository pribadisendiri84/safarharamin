<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\PackageItinerary;
use App\Services\PackageImageStore;
use App\Services\PackageItineraryStore;
use App\Support\PackageCardBadge;
use App\Support\TableSort;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackageController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Package::query()->with(['packageKind']),
            $request
        );

        TableSort::apply($query, $request, [
            'title' => 'title',
            'departure_date' => 'departure_date',
            'seats_left' => 'seats_left',
            'status' => 'status',
            'updated_at' => 'updated_at',
        ], 'updated_at', 'desc');

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

        if ($departureFrom = $request->date('departure_from')) {
            $query->whereNotNull('departure_date')
                ->whereDate('departure_date', '>=', $departureFrom);
        }

        if ($departureTo = $request->date('departure_to')) {
            $query->whereNotNull('departure_date')
                ->whereDate('departure_date', '<=', $departureTo);
        }

        if ($request->boolean('needs_flyer')) {
            $query->needsFlyer();
        }

        if ($request->boolean('expiring_soon')) {
            $query->visibleOnCatalog()->upcomingDeparture()->expiringSoon();
        }

        if ($request->boolean('low_seats')) {
            $query->visibleOnCatalog()->upcomingDeparture()->lowSeatsRemaining();
        }

        $packages = $query->paginate(50)->withQueryString();

        return view('admin.packages.index', [
            'packages' => $packages,
            'homePackages' => $request->boolean('trashed') ? collect() : Package::homeItemsForAdmin(),
            'hasActiveFilters' => $request->hasAny([
                'q', 'status', 'data_complete', 'departure_from', 'departure_to', 'featured', 'needs_flyer',
                'expiring_soon', 'low_seats',
            ]),
            ...$this->trashViewData(Package::class, $request),
        ]);
    }

    public function create()
    {
        return view('admin.packages.form', ['package' => new Package]);
    }

    public function store(Request $request, PackageImageStore $images, PackageItineraryStore $itineraries)
    {
        $data = $this->validated($request);
        $data['slug'] = Package::uniqueSlug($data['title']);
        $data['images'] = $this->collectImages($request, $images, $data['title']);
        $data['cover_image'] = $this->collectCover($request, $images, $data['title']);
        $data['facilities'] = $this->lines($request->input('facilities_text'));
        $data['exclusions'] = $this->lines($request->input('exclusions_text'));

        $package = Package::query()->create($data);
        $this->syncItineraries($request, $package, $itineraries);

        return redirect()->route('admin.packages.index')->with('ok', 'Paket ditambahkan.');
    }

    public function edit(Package $package)
    {
        $package->load(['pdfItineraries']);

        return view('admin.packages.form', ['package' => $package]);
    }

    public function update(Request $request, Package $package, PackageImageStore $images, PackageItineraryStore $itineraries)
    {
        $data = $this->validated($request, $package);
        $data['images'] = $this->collectImages($request, $images, $data['title'], $package->images ?? []);
        $data['cover_image'] = $this->collectCover($request, $images, $data['title'], $package->cover_image);
        $data['facilities'] = $this->lines($request->input('facilities_text'));
        $data['exclusions'] = $this->lines($request->input('exclusions_text'));

        $package->update($data);
        $this->syncItineraries($request, $package, $itineraries);

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
        $copy->departure_date_end = null;
        $copy->departure_date_display = 'single';
        $copy->images = [];
        $copy->cover_image = null;
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

        $package->update(['status' => $data['status']]);

        return $this->packageStatusResponse($package->fresh(), 'Status paket diperbarui.');
    }

    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'package_ids' => ['required', 'array', 'min:1'],
            'package_ids.*' => ['integer', 'exists:packages,id'],
            'status' => ['required', Rule::in(array_keys(Package::STATUSES))],
        ]);

        $updated = 0;

        Package::query()
            ->whereIn('id', array_map('intval', $data['package_ids']))
            ->each(function (Package $package) use ($data, &$updated) {
                if ($package->status === $data['status']) {
                    return;
                }

                $package->update(['status' => $data['status']]);
                $updated++;
            });

        $label = Package::STATUSES[$data['status']] ?? $data['status'];

        return redirect()
            ->back()
            ->with('ok', $updated.' paket diubah ke status '.$label.'.');
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
                'meta' => $package->departureLine().' · '.$package->formattedStartingPrice(),
                'edit_url' => route('admin.packages.edit', $package),
                'remove_url' => route('admin.packages.toggle-featured', $package),
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
        $this->normalizeRoomPrices($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in(array_keys(Package::TYPES))],
            'package_kind_id' => [
                'required',
                Rule::exists('package_kinds', 'id')->where(function ($query) use ($existing) {
                    $query->whereNull('deleted_at')
                        ->where(function ($inner) use ($existing) {
                            $inner->where('is_active', true);
                            if ($existing?->package_kind_id) {
                                $inner->orWhere('id', $existing->package_kind_id);
                            }
                        });
                }),
            ],
            'departure_city' => ['required', Rule::exists('cities', 'slug')->whereNull('deleted_at')],
            'arrival_city' => ['nullable', Rule::in(array_keys(Package::ARRIVAL_CITIES))],
            'departure_date' => ['nullable', 'date', 'required_if:departure_date_display,range'],
            'departure_date_end' => ['nullable', 'date', 'required_if:departure_date_display,range', 'after_or_equal:departure_date'],
            'departure_date_display' => ['nullable', Rule::in(array_keys(Package::DEPARTURE_DATE_DISPLAYS))],
            'duration_days' => ['required', 'integer', 'min:7', 'max:45'],
            'price_quad' => ['nullable', 'integer', 'min:1'],
            'price_triple' => ['nullable', 'integer', 'min:1'],
            'price_double' => ['nullable', 'integer', 'min:1'],
            'price_double_plus' => ['nullable', 'integer', 'min:1'],
            'original_price' => ['nullable', 'integer', 'min:1'],
            'price_note' => ['nullable', 'string', 'max:180'],
            'price_label' => ['nullable', 'string', 'max:40'],
            'hotel_makkah' => ['nullable', 'string', 'max:120'],
            'hotel_madinah' => ['nullable', 'string', 'max:120'],
            'hotel_transit' => ['nullable', 'string', 'max:120'],
            'hotel_maktab' => ['nullable', 'string', 'max:120'],
            'airline' => ['nullable', 'string', 'max:80'],
            'seats_total' => ['required', 'integer', 'min:1'],
            'seats_left' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Package::STATUSES))],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'cover_photo' => ['nullable', 'image', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],
            'remove_flyer' => ['nullable', 'boolean'],
            'facilities_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
            'itinerary_departure_dates' => ['nullable', 'array'],
            'itinerary_departure_dates.*' => ['nullable', 'date'],
            'itinerary_pdfs' => ['nullable', 'array'],
            'itinerary_pdfs.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'delete_itineraries' => ['nullable', 'array'],
            'delete_itineraries.*' => ['integer', 'exists:package_itineraries,id'],
            'card_badge_preset' => ['nullable', 'string', Rule::in(array_keys(PackageCardBadge::presets()))],
            'card_badge_icon' => ['nullable', 'string', Rule::in(array_keys(PackageCardBadge::icons()))],
            'card_badge_text' => ['nullable', 'string', 'max:40'],
            'card_badge_position' => ['nullable', 'string', Rule::in(array_keys(PackageCardBadge::positions()))],
        ]);

        unset($data['facilities_text'], $data['exclusions_text'], $data['photos'], $data['cover_photo']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_hot'] = $request->boolean('is_hot');
        $data['card_badge_preset'] = $data['is_hot']
            ? PackageCardBadge::normalizePreset($data['card_badge_preset'] ?? null)
            : null;
        $data['card_badge_icon'] = $data['is_hot']
            ? PackageCardBadge::normalizeIcon($data['card_badge_icon'] ?? null)
            : null;
        $data['card_badge_text'] = $data['is_hot']
            ? (trim((string) ($data['card_badge_text'] ?? '')) ?: PackageCardBadge::presetLabel($data['card_badge_preset']))
            : null;
        $data['card_badge_position'] = $data['is_hot']
            ? PackageCardBadge::normalizePosition($data['card_badge_position'] ?? null)
            : null;
        $data['show_seats'] = $request->has('show_seats')
            ? $request->boolean('show_seats')
            : ($existing?->show_seats ?? true);
        $data['hotel_makkah_setaraf'] = $request->boolean('hotel_makkah_setaraf');
        $data['hotel_madinah_setaraf'] = $request->boolean('hotel_madinah_setaraf');
        $data['departure_date_display'] = $data['departure_date_display']
            ?? $existing?->departure_date_display
            ?? 'single';
        if ($data['departure_date_display'] !== 'range') {
            $data['departure_date_end'] = null;
        }
        if (($data['arrival_city'] ?? '') === '') {
            $data['arrival_city'] = null;
        }
        $data['home_sort'] = $this->resolveHomeSort($data['is_featured'], $existing);

        if (! in_array($data['type'], Package::HAJI_TYPES, true)) {
            $data['price_double_plus'] = null;
            $data['hotel_transit'] = null;
            $data['hotel_maktab'] = null;
        }

        return $data;
    }

    private function normalizeRoomPrices(Request $request): void
    {
        foreach (['price_quad', 'price_triple', 'price_double', 'price_double_plus'] as $field) {
            $raw = $request->input($field);
            if ($raw === null || $raw === '') {
                $request->merge([$field => null]);

                continue;
            }

            $digits = preg_replace('/\D/', '', (string) $raw);
            $request->merge([$field => $digits !== '' ? (int) $digits : null]);
        }
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
        if ($request->boolean('remove_flyer')) {
            foreach ($existing as $path) {
                $this->deleteStoredImage($path);
            }
            $existing = [];
        }

        $urls = [];

        foreach ($request->file('photos', []) as $file) {
            if ($file && $file->isValid()) {
                $urls[] = $store->store($file, $title);
            }
        }

        if ($urls !== []) {
            foreach ($existing as $path) {
                $this->deleteStoredImage($path);
            }

            return $urls;
        }

        return $existing;
    }

    private function collectCover(
        Request $request,
        PackageImageStore $store,
        string $title,
        ?string $existing = null,
    ): ?string {
        if ($request->boolean('remove_cover')) {
            $this->deleteStoredImage($existing);
            $existing = null;
        }

        $file = $request->file('cover_photo');
        if ($file && $file->isValid()) {
            $this->deleteStoredImage($existing);

            return $store->storeCover($file, $title);
        }

        return $existing;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(ltrim(substr($path, strlen('/storage/')), '/'));
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

    private function syncItineraries(Request $request, Package $package, PackageItineraryStore $store): void
    {
        $deleteIds = array_map('intval', $request->input('delete_itineraries', []));
        if ($deleteIds !== []) {
            PackageItinerary::query()
                ->where('package_id', $package->id)
                ->whereIn('id', $deleteIds)
                ->get()
                ->each(function (PackageItinerary $item) use ($store) {
                    $store->delete($item->file_path);
                    $item->delete();
                });
        }

        foreach ($request->input('itinerary_departure_dates', []) as $index => $dateRaw) {
            $date = trim((string) $dateRaw);
            $files = $request->file('itinerary_pdfs', []);
            $file = is_array($files) ? ($files[$index] ?? null) : null;

            if ($date === '' && ! $file) {
                continue;
            }

            if ($date === '') {
                throw ValidationException::withMessages([
                    "itinerary_departure_dates.{$index}" => 'Isi tanggal keberangkatan untuk itinerary PDF.',
                ]);
            }

            if (! $file || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    "itinerary_pdfs.{$index}" => 'Unggah file PDF itinerary.',
                ]);
            }

            PackageItinerary::query()->create([
                'package_id' => $package->id,
                'kind' => PackageItinerary::KIND_OFFICIAL,
                'departure_date' => $date,
                'file_path' => $store->store($file, $package->title.' '.$date),
            ]);
        }

        PackageItinerary::syncSortOrderForPackage($package->id);
    }
}

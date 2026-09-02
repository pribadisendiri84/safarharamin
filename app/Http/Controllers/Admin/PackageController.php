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

        if ($q = trim((string) $request->input('q'))) {
            $query->where('title', 'like', '%'.$q.'%');
        }

        return view('admin.packages.index', [
            'packages' => $query->paginate(20)->withQueryString(),
            ...$this->trashViewData(Package::class, $request),
        ]);
    }

    public function create()
    {
        return view('admin.packages.form', ['package' => new Package]);
    }

    public function store(Request $request, PackageImageStore $images)
    {
        $data = $this->validated($request, requireFlyer: true);
        $data['slug'] = Package::uniqueSlug($data['title']);
        $data['images'] = $this->collectImages($request, $images, $data['title']);
        $this->assertFlyerPresent($data['images']);
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
        $data = $this->validated($request);
        $data['images'] = $this->collectImages($request, $images, $data['title'], $package->images ?? []);
        $this->assertFlyerPresent($data['images']);
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

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $requireFlyer = false): array
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
            'photos' => $requireFlyer ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'facilities_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
        ]);

        unset($data['facilities_text'], $data['exclusions_text'], $data['photos']);
        $data['price'] = $data['price_quad'];
        $data['room_type'] = 'quad';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_hot'] = $request->boolean('is_hot');

        return $data;
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
    private function assertFlyerPresent(array $images): void
    {
        if ($images === []) {
            throw ValidationException::withMessages([
                'photos' => 'Unggah flyer paket. Flyer wajib jadi cover.',
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

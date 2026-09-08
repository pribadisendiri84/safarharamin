<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Package;
use App\Services\HajiPageItineraryStore;
use App\Support\HajiPlusPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartureController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $kindFilter = $request->string('kind')->toString();
        if (! array_key_exists($kindFilter, Departure::KINDS)) {
            $kindFilter = '';
        }

        $query = $this->applyTrashFilter(
            Departure::query()->withCount('pilgrims')->latest('departure_date'),
            $request
        );

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('program_name', 'like', "%{$q}%")
                    ->orWhere('airline', 'like', "%{$q}%")
                    ->orWhere('flight_number', 'like', "%{$q}%");
            });
        }

        if ($kindFilter !== '') {
            $query->where('program_kind', $kindFilter);
        }

        return view('admin.operations.departures.index', [
            'departures' => $query->paginate(20)->withQueryString(),
            'kindFilter' => $kindFilter,
            ...$this->trashViewData(Departure::class, $request),
        ]);
    }

    public function create(Request $request)
    {
        if ($request->string('from')->toString() === 'haji_page' || $request->string('kind')->toString() === 'haji') {
            return view('admin.operations.departures.form', [
                'departure' => new Departure(array_merge(
                    HajiPlusPage::departureDefaults(),
                    [
                        'source' => Departure::SOURCE_HAJI_PAGE,
                        'program_kind' => 'haji',
                        'package_id' => null,
                    ],
                )),
                'fromHajiPage' => true,
                ...$this->departureFormData(),
            ]);
        }

        $package = $request->filled('package_id')
            ? Package::query()->find($request->integer('package_id'))
            : null;

        $defaults = $package?->departureDefaults() ?? [];

        return view('admin.operations.departures.form', [
            'departure' => new Departure(array_merge(
                ['program_kind' => 'umroh', 'source' => $package ? Departure::SOURCE_PACKAGE : Departure::SOURCE_MANUAL],
                $defaults,
                ['package_id' => $package?->id],
            )),
            'fromHajiPage' => false,
            ...$this->departureFormData(),
        ]);
    }

    public function store(Request $request, HajiPageItineraryStore $itineraryStore)
    {
        $departure = Departure::query()->create($this->validated($request, null, $itineraryStore));

        return redirect()
            ->route('admin.operations.departures.index', $this->indexRedirectParams($departure))
            ->with('ok', 'Keberangkatan berhasil ditambahkan.');
    }

    public function edit(Departure $departure)
    {
        return view('admin.operations.departures.form', [
            'departure' => $departure,
            'fromHajiPage' => $departure->isFromHajiPage(),
            ...$this->departureFormData(),
        ]);
    }

    public function update(Request $request, Departure $departure, HajiPageItineraryStore $itineraryStore)
    {
        $departure->update($this->validated($request, $departure, $itineraryStore));

        return redirect()
            ->route('admin.operations.departures.index', $this->indexRedirectParams($departure))
            ->with('ok', 'Keberangkatan diperbarui.');
    }

    public function destroy(Departure $departure)
    {
        $params = $this->indexRedirectParams($departure);
        $departure->delete();

        return redirect()
            ->route('admin.operations.departures.index', $params)
            ->with('ok', 'Keberangkatan dihapus.');
    }

    public function restore(int $departure)
    {
        Departure::query()->onlyTrashed()->findOrFail($departure)->restore();

        return redirect()
            ->route('admin.operations.departures.index', ['trashed' => 1])
            ->with('ok', 'Keberangkatan dipulihkan.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Departure $existing, HajiPageItineraryStore $itineraryStore): array
    {
        $data = $request->validate([
            'package_id' => ['nullable', 'exists:packages,id'],
            'source' => ['nullable', Rule::in([
                Departure::SOURCE_MANUAL,
                Departure::SOURCE_PACKAGE,
                Departure::SOURCE_HAJI_PAGE,
            ])],
            'program_name' => ['required', 'string', 'max:180'],
            'program_kind' => ['required', Rule::in(array_keys(Departure::KINDS))],
            'departure_date' => ['nullable', 'date'],
            'hijri_label' => ['nullable', 'string', 'max:80'],
            'itinerary_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_itinerary_pdf' => ['nullable', 'boolean'],
            'airline' => ['nullable', 'string', 'max:120'],
            'flight_number' => ['nullable', 'string', 'max:60'],
            'hotel_makkah' => ['nullable', 'string', 'max:180'],
            'hotel_madinah' => ['nullable', 'string', 'max:180'],
            'hotel_transit' => ['nullable', 'string', 'max:180'],
            'hotel_maktab' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['program_kind'] ?? '') !== 'haji') {
            unset($data['hijri_label']);
            $data['itinerary_pdf_path'] = null;
        } else {
            $data['hijri_label'] = trim((string) ($data['hijri_label'] ?? '')) ?: null;
            $data['itinerary_pdf_path'] = $this->resolveItineraryPdfPath(
                $request,
                $existing,
                $itineraryStore,
                $data['departure_date'] ?? null,
            );
        }

        unset($data['itinerary_pdf'], $data['remove_itinerary_pdf']);

        return $this->applyOperationalSnapshot($data, $existing);
    }

    private function resolveItineraryPdfPath(
        Request $request,
        ?Departure $existing,
        HajiPageItineraryStore $store,
        ?string $departureDate,
    ): ?string {
        $previous = $existing?->itinerary_pdf_path;

        if ($request->boolean('remove_itinerary_pdf')) {
            $store->delete($previous);

            return null;
        }

        if ($request->hasFile('itinerary_pdf') && $request->file('itinerary_pdf')->isValid()) {
            $label = 'haji-itinerary '.($departureDate ?: ($existing?->program_name ?? 'program'));
            $path = $store->store($request->file('itinerary_pdf'), $label);
            $store->delete($previous);

            return $path;
        }

        return $previous;
    }

    /** @param  array<string, mixed>  $data */
    private function applyOperationalSnapshot(array $data, ?Departure $existing = null): array
    {
        if (($data['program_kind'] ?? '') !== 'haji') {
            return $data;
        }

        if (($data['source'] ?? Departure::SOURCE_MANUAL) === Departure::SOURCE_HAJI_PAGE) {
            $data['package_id'] = null;
        }

        if ($existing?->program_snapshot) {
            return $data;
        }

        $data['program_snapshot'] = HajiPlusPage::operationalSnapshot();

        return $data;
    }

    /** @return array{packages: \Illuminate\Support\Collection<int, Package>, packageCatalog: array<int, array<string, string|null>>} */
    private function departureFormData(): array
    {
        $packages = Package::query()
            ->whereNotIn('type', Package::HAJI_TYPES)
            ->orderBy('title')
            ->get();

        return [
            'packages' => $packages,
            'packageCatalog' => $packages
                ->mapWithKeys(fn (Package $package) => [$package->id => $package->departureDefaults()])
                ->all(),
        ];
    }

    /** @return array<string, string> */
    private function indexRedirectParams(Departure $departure): array
    {
        return array_filter([
            'kind' => $departure->program_kind,
        ]);
    }
}

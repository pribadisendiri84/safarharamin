<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartureController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
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

        if ($kind = $request->string('kind')->toString()) {
            $query->where('program_kind', $kind);
        }

        return view('admin.operations.departures.index', [
            'departures' => $query->paginate(20)->withQueryString(),
            ...$this->trashViewData(Departure::class, $request),
        ]);
    }

    public function create(Request $request)
    {
        $package = $request->filled('package_id')
            ? Package::query()->find($request->integer('package_id'))
            : null;

        $defaults = $package?->departureDefaults() ?? [];

        return view('admin.operations.departures.form', [
            'departure' => new Departure(array_merge(
                ['program_kind' => 'umroh'],
                $defaults,
                ['package_id' => $package?->id],
            )),
            ...$this->departureFormData(),
        ]);
    }

    public function store(Request $request)
    {
        $departure = Departure::query()->create($this->validated($request));

        return redirect()
            ->route('admin.operations.departures.index')
            ->with('ok', 'Keberangkatan berhasil ditambahkan.');
    }

    public function edit(Departure $departure)
    {
        return view('admin.operations.departures.form', [
            'departure' => $departure,
            ...$this->departureFormData(),
        ]);
    }

    public function update(Request $request, Departure $departure)
    {
        $departure->update($this->validated($request));

        return redirect()
            ->route('admin.operations.departures.index')
            ->with('ok', 'Keberangkatan diperbarui.');
    }

    public function destroy(Departure $departure)
    {
        $departure->delete();

        return redirect()
            ->route('admin.operations.departures.index')
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
    private function validated(Request $request): array
    {
        return $request->validate([
            'package_id' => ['nullable', 'exists:packages,id'],
            'program_name' => ['required', 'string', 'max:180'],
            'program_kind' => ['required', Rule::in(array_keys(Departure::KINDS))],
            'departure_date' => ['nullable', 'date'],
            'airline' => ['nullable', 'string', 'max:120'],
            'flight_number' => ['nullable', 'string', 'max:60'],
            'hotel_makkah' => ['nullable', 'string', 'max:180'],
            'hotel_madinah' => ['nullable', 'string', 'max:180'],
            'hotel_transit' => ['nullable', 'string', 'max:180'],
            'hotel_maktab' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    /** @return array{packages: \Illuminate\Support\Collection<int, Package>, packageCatalog: array<int, array<string, string|null>>} */
    private function departureFormData(): array
    {
        $packages = Package::query()->orderBy('title')->get();

        return [
            'packages' => $packages,
            'packageCatalog' => $packages
                ->mapWithKeys(fn (Package $package) => [$package->id => $package->departureDefaults()])
                ->all(),
        ];
    }
}

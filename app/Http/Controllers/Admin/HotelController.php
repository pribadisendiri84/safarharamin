<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Hotel;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HotelController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $location = $request->string('location')->toString();
        $query = $this->applyTrashFilter(
            Hotel::query()->with('creator')->orderBy('sort_order')->orderBy('name'),
            $request
        );

        if ($location !== '' && array_key_exists($location, Hotel::LOCATIONS)) {
            $query->where('location', $location);
        }

        return view('admin.hotels.index', [
            'hotels' => $query->get(),
            'location' => $location,
            ...$this->trashViewData(Hotel::class, $request),
        ]);
    }

    public function store(Request $request)
    {
        Hotel::query()->create($this->validated($request));

        return redirect()->route('admin.hotels.index', $this->redirectQuery($request))
            ->with('ok', 'Hotel ditambahkan.');
    }

    public function update(Request $request, Hotel $hotel)
    {
        $hotel->update($this->validated($request, $hotel));

        return redirect()->route('admin.hotels.index', $this->redirectQuery($request))
            ->with('ok', 'Hotel diperbarui.');
    }

    public function destroy(Hotel $hotel)
    {
        if ($this->isUsed($hotel)) {
            return back()->withErrors('Hotel masih dipakai paket atau keberangkatan. Nonaktifkan saja, jangan hapus.');
        }

        $hotel->delete();

        return redirect()->route('admin.hotels.index', ['location' => $hotel->location])
            ->with('ok', 'Hotel dihapus.');
    }

    public function restore(Hotel $hotel)
    {
        $hotel->restore();

        return redirect()->route('admin.hotels.index', ['location' => $hotel->location, 'trashed' => 1])
            ->with('ok', 'Hotel dipulihkan.');
    }

    /**
     * @return array{name: string, location: string, stars: int, sort_order: int, is_active: bool}
     */
    private function validated(Request $request, ?Hotel $hotel = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('hotels', 'name')
                    ->where('location', $request->input('location'))
                    ->ignore($hotel)
                    ->whereNull('deleted_at'),
            ],
            'location' => ['required', Rule::in(array_keys(Hotel::LOCATIONS))],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'location' => $data['location'],
            'stars' => (int) $data['stars'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function isUsed(Hotel $hotel): bool
    {
        $column = match ($hotel->location) {
            Hotel::LOCATION_MAKKAH => 'hotel_makkah',
            Hotel::LOCATION_MADINAH => 'hotel_madinah',
            Hotel::LOCATION_TRANSIT => 'hotel_transit',
            Hotel::LOCATION_MAKTAB => 'hotel_maktab',
            default => null,
        };

        if ($column === null) {
            return false;
        }

        return Package::withTrashed()->where($column, $hotel->name)->exists()
            || Departure::withTrashed()->where($column, $hotel->name)->exists();
    }

    /** @return array<string, mixed> */
    private function redirectQuery(Request $request): array
    {
        $location = $request->string('location')->toString();

        return $location !== '' ? ['location' => $location] : [];
    }
}

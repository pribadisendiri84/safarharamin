<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Inquiry;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            City::query()->with('creator')->orderBy('sort_order')->orderBy('name'),
            $request
        );

        return view('admin.cities.index', [
            'cities' => $query->get(),
            ...$this->trashViewData(City::class, $request),
        ]);
    }

    public function store(Request $request)
    {
        City::query()->create($this->validated($request));

        return redirect()->route('admin.cities.index')->with('ok', 'Kota ditambahkan.');
    }

    public function update(Request $request, City $city)
    {
        $city->update($this->validated($request, $city));

        return redirect()->route('admin.cities.index')->with('ok', 'Kota diperbarui.');
    }

    public function destroy(City $city)
    {
        $used = Package::withTrashed()->where('departure_city', $city->slug)->exists()
            || Inquiry::withTrashed()->where('city', $city->slug)->exists();

        if ($used) {
            return back()->withErrors('Kota masih dipakai paket atau pengajuan. Nonaktifkan saja, jangan hapus.');
        }

        $city->delete();

        return redirect()->route('admin.cities.index')->with('ok', 'Kota dihapus.');
    }

    public function restore(City $city)
    {
        $city->restore();

        return redirect()->route('admin.cities.index', ['trashed' => 1])->with('ok', 'Kota dipulihkan.');
    }

    /**
     * @return array{name: string, sort_order: int, is_active: bool}
     */
    private function validated(Request $request, ?City $city = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('cities', 'name')->ignore($city)->whereNull('deleted_at')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

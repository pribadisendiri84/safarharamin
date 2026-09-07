<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Departure;
use App\Models\Package;
use App\Services\PackageImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AirlineController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Airline::query()->with('creator')->orderBy('sort_order')->orderBy('name'),
            $request
        );

        return view('admin.airlines.index', [
            'airlines' => $query->get(),
            ...$this->trashViewData(Airline::class, $request),
        ]);
    }

    public function store(Request $request, PackageImageStore $images)
    {
        $data = $this->validated($request);
        if ($request->hasFile('logo')) {
            $data['logo'] = $images->store($request->file('logo'), $data['name'], 'airlines');
        }

        Airline::query()->create($data);

        return redirect()->route('admin.airlines.index')->with('ok', 'Maskapai ditambahkan.');
    }

    public function update(Request $request, Airline $airline, PackageImageStore $images)
    {
        $data = $this->validated($request, $airline);
        $previousLogo = $airline->logo;

        if ($request->boolean('remove_logo')) {
            $data['logo'] = null;
        }
        if ($request->hasFile('logo')) {
            $data['logo'] = $images->store($request->file('logo'), $data['name'], 'airlines');
        }

        $airline->update($data);
        if (array_key_exists('logo', $data) && $previousLogo !== $airline->logo) {
            $this->deleteStoredLogo($previousLogo);
        }

        return redirect()->route('admin.airlines.index')->with('ok', 'Maskapai diperbarui.');
    }

    public function destroy(Airline $airline)
    {
        if ($this->isUsed($airline)) {
            return back()->withErrors('Maskapai masih dipakai paket atau keberangkatan. Nonaktifkan saja, jangan hapus.');
        }

        $airline->delete();

        return redirect()->route('admin.airlines.index')->with('ok', 'Maskapai dihapus.');
    }

    public function restore(Airline $airline)
    {
        $airline->restore();

        return redirect()->route('admin.airlines.index', ['trashed' => 1])
            ->with('ok', 'Maskapai dipulihkan.');
    }

    /**
     * @return array{name: string, sort_order: int, is_active: bool}
     */
    private function validated(Request $request, ?Airline $airline = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('airlines', 'name')->ignore($airline)->whereNull('deleted_at')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function deleteStoredLogo(?string $path): void
    {
        if (! $path || ! str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(ltrim(substr($path, strlen('/storage/')), '/'));
    }

    private function isUsed(Airline $airline): bool
    {
        return Package::withTrashed()->where('airline', $airline->name)->exists()
            || Departure::withTrashed()->where('airline', $airline->name)->exists();
    }
}

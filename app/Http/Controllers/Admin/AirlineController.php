<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Departure;
use App\Models\Package;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        Airline::query()->create($this->validated($request));

        return redirect()->route('admin.airlines.index')->with('ok', 'Maskapai ditambahkan.');
    }

    public function update(Request $request, Airline $airline)
    {
        $airline->update($this->validated($request, $airline));

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
        ]);

        return [
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function isUsed(Airline $airline): bool
    {
        return Package::withTrashed()->where('airline', $airline->name)->exists()
            || Departure::withTrashed()->where('airline', $airline->name)->exists();
    }
}

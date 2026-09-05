<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Package;
use App\Models\PackageKind;
use App\Support\WaMessages;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $query = Package::query()->visibleOnCatalog()->with('packageKind');
        $type = $request->string('tipe')->toString();
        $group = $request->string('kelompok')->toString();

        if ($type !== '') {
            $query->where('type', $type);
        } elseif ($group === 'umroh') {
            $query->whereIn('type', Package::UMROH_TYPES);
        } elseif ($group === 'haji') {
            $query->whereIn('type', Package::HAJI_TYPES);
        }

        if ($kind = $request->string('jenis')->toString()) {
            $query->whereHas('packageKind', fn ($builder) => $builder->where('slug', $kind));
        }

        if ($city = $request->string('kota')->toString()) {
            $query->where('departure_city', $city);
        }

        if ($airline = $request->string('maskapai')->toString()) {
            $query->where('airline', $airline);
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', '%'.$q.'%')
                    ->orWhere('hotel_makkah', 'like', '%'.$q.'%')
                    ->orWhere('airline', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('harga_max')) {
            $query->where('price', '<=', (int) $request->input('harga_max'));
        }

        if ($request->filled('hari')) {
            $query->where('duration_days', '<=', (int) $request->input('hari'));
        }

        $sort = $request->string('urut')->toString();
        match ($sort) {
            'termurah' => $query->orderBy('price'),
            'termahal' => $query->orderByDesc('price'),
            'terdekat' => $query->orderBy('departure_date'),
            default => $query->orderByDesc('is_hot')->orderBy('departure_date'),
        };

        $packages = $query->paginate(12)->withQueryString();
        $typeLabel = match (true) {
            ($type ?? '') !== '' => Package::TYPES[$type] ?? 'Semua paket',
            $group === 'umroh' => 'Paket umroh',
            $group === 'haji' => 'Paket haji',
            default => 'Semua paket',
        };

        $chipQuery = Package::query()->published();
        if ($type !== '') {
            $chipQuery->where('type', $type);
        } elseif ($group === 'umroh') {
            $chipQuery->whereIn('type', Package::UMROH_TYPES);
        } elseif ($group === 'haji') {
            $chipQuery->whereIn('type', Package::HAJI_TYPES);
        }
        $chipCities = $chipQuery->distinct()->pluck('departure_city');

        return view('packages.index', [
            'packages' => $packages,
            'typeLabel' => $typeLabel,
            'chipCities' => $chipCities,
            'packageKinds' => PackageKind::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['slug', 'name']),
            'filters' => $request->only(['q', 'tipe', 'kelompok', 'jenis', 'kota', 'maskapai', 'harga_max', 'hari', 'urut']),
        ]);
    }

    public function show(Package $package)
    {
        abort_unless($package->isVisibleOnCatalog(), 404);

        $package->loadMissing('packageKind');

        $related = Package::query()
            ->with('packageKind')
            ->published()
            ->where('id', '!=', $package->id)
            ->where('type', $package->type)
            ->orderBy('price')
            ->limit(4)
            ->get();

        return view('packages.show', [
            'package' => $package,
            'related' => $related,
        ]);
    }

    public function inquire(Request $request, Package $package)
    {
        abort_unless($package->status === 'published', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'pax' => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Inquiry::query()->create([
            ...$data,
            'kind' => 'tanya',
            'source' => Inquiry::SOURCE_WEBSITE,
            'city' => $package->departure_city,
            'package_id' => $package->id,
            'status' => 'baru',
        ]);

        $message = WaMessages::packageInquiry($package, $data['name'], $data['phone']);

        $request->session()->put('wa_text', $message);

        return redirect()->route('go.whatsapp', ['from' => 'form']);
    }
}

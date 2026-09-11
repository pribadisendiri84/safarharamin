<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageKind;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index(Request $request)
    {
        $query = Package::query()
            ->publiclyVisible()
            ->with('packageKind')
            ->orderByDesc('departure_date')
            ->orderBy('airline')
            ->orderBy('title');

        if ($type = $request->string('tipe')->toString()) {
            $query->where('type', $type);
        }

        if ($kind = $request->string('jenis')->toString()) {
            $query->whereHas('packageKind', fn ($builder) => $builder->where('slug', $kind));
        }

        if ($airline = $request->string('maskapai')->toString()) {
            $query->where('airline', $airline);
        }

        if ($city = $request->string('kota')->toString()) {
            $query->where('departure_city', $city);
        }

        if ($from = $request->string('dari')->toString()) {
            $query->whereDate('departure_date', '>=', $from);
        }

        if ($to = $request->string('sampai')->toString()) {
            $query->whereDate('departure_date', '<=', $to);
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where('title', 'like', '%'.$q.'%');
        }

        $packages = $query->get();

        $groups = $packages
            ->groupBy(fn (Package $package) => $package->typeLabel())
            ->map(fn ($typeGroup) => $typeGroup
                ->groupBy(fn (Package $package) => $package->airline ?: 'Maskapai menyusul')
                ->sortKeys());

        return view('pages.price-list', [
            'groups' => $groups,
            'total' => $packages->count(),
            'packageKinds' => PackageKind::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => [
                'q' => $request->input('q'),
                'tipe' => $request->input('tipe'),
                'jenis' => $request->input('jenis'),
                'maskapai' => $request->input('maskapai'),
                'kota' => $request->input('kota'),
                'dari' => $request->input('dari'),
                'sampai' => $request->input('sampai'),
            ],
        ]);
    }
}

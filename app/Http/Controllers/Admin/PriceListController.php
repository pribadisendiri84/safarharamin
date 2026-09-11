<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Package;
use App\Models\PriceSyncRun;
use App\Support\PriceSyncSchedule;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index(Request $request)
    {
        $query = Package::query()
            ->with('packageKind')
            ->orderByDesc('departure_date')
            ->orderBy('airline')
            ->orderBy('title');

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($airline = $request->string('airline')->toString()) {
            $query->where('airline', $airline);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($from = $request->string('departure_from')->toString()) {
            $query->whereDate('departure_date', '>=', $from);
        }

        if ($to = $request->string('departure_to')->toString()) {
            $query->whereDate('departure_date', '<=', $to);
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where('title', 'like', '%'.$q.'%');
        }

        if ($request->boolean('only_arminareka')) {
            $query->where('source_key', 'like', 'arminareka:%');
        }

        $packages = $query->get();

        $groups = $packages
            ->groupBy(fn (Package $package) => $package->typeLabel())
            ->map(fn ($typeGroup) => $typeGroup
                ->groupBy(fn (Package $package) => $package->airline ?: 'Tanpa maskapai')
                ->sortKeys());

        $lastSync = PriceSyncRun::query()->latest()->first();
        $pendingRun = PriceSyncRun::query()
            ->where('status', PriceSyncRun::STATUS_WAITING)
            ->latest()
            ->first();

        return view('admin.price-list.index', [
            'groups' => $groups,
            'filters' => [
                'types' => Package::TYPES,
                'airlines' => Airline::options(),
                'statuses' => Package::STATUSES,
            ],
            'stats' => [
                'total' => Package::query()->count(),
                'published' => Package::query()->where('status', 'published')->count(),
                'hidden' => Package::query()->whereIn('status', ['draft', 'hide'])->count(),
                'pending_changes' => $pendingRun
                    ? $pendingRun->total_new + $pendingRun->total_changed + $pendingRun->total_removed
                    : 0,
            ],
            'lastSync' => $lastSync,
            'pendingRun' => $pendingRun,
            'schedule' => PriceSyncSchedule::config(),
            'nextScheduledSync' => PriceSyncSchedule::nextRunAt(),
        ]);
    }
}

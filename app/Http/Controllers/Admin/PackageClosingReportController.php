<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Package;
use App\Support\TableSort;
use Illuminate\Http\Request;

class PackageClosingReportController extends Controller
{
    public function index(Request $request)
    {
        $packages = Package::query()
            ->withSum(['inquiries as sold_pax' => fn ($q) => $q->where('status', Inquiry::STATUS_SOLD)], 'sold_pax')
            ->withSum(['inquiries as sold_amount' => fn ($q) => $q->where('status', Inquiry::STATUS_SOLD)], 'sold_amount')
            ->whereHas('inquiries', fn ($q) => $q->where('status', Inquiry::STATUS_SOLD))
            ->when($q = trim((string) $request->input('q')), fn ($query) => $query->where('title', 'like', '%'.$q.'%'))
            ->when($departureFrom = $request->date('departure_from'), fn ($query) => $query->whereDate('departure_date', '>=', $departureFrom))
            ->when($departureTo = $request->date('departure_to'), fn ($query) => $query->whereDate('departure_date', '<=', $departureTo));

        TableSort::apply($packages, $request, [
            'title' => 'title',
            'departure_date' => 'departure_date',
            'sold_pax' => 'sold_pax',
            'sold_amount' => 'sold_amount',
            'seats_left' => 'seats_left',
        ], 'departure_date', 'asc');

        $packages = $packages->paginate(50)
            ->withQueryString();

        return view('admin.reports.closing', [
            'packages' => $packages,
            'hasActiveFilters' => $request->hasAny(['q', 'departure_from', 'departure_to']),
        ]);
    }
}

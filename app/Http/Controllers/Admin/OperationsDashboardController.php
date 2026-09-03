<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Pilgrim;
use App\Models\Room;

class OperationsDashboardController extends Controller
{
    public function __invoke()
    {
        $departures = Departure::query()->withCount('pilgrims')->latest('departure_date')->limit(6)->get();
        $stats = [
            'total_pilgrims' => Pilgrim::query()->count(),
            'total_quad' => Pilgrim::query()->where('room_type', 'quad')->count(),
            'total_triple' => Pilgrim::query()->where('room_type', 'triple')->count(),
            'total_double' => Pilgrim::query()->where('room_type', 'double')->count(),
            'total_double_plus' => Pilgrim::query()->where('room_type', 'double_plus')->count(),
            'total_rooms' => Room::query()->count(),
            'rooms_full' => 0,
            'rooms_incomplete' => 0,
            'pilgrims_ungrouped' => Pilgrim::query()->whereNull('room_id')->count(),
            'pilgrims_lunas' => 0,
            'pilgrims_cicilan' => 0,
            'pilgrims_belum_bayar' => 0,
            'pilgrims_overpaid' => 0,
            'total_overpayment' => 0,
            'total_collected' => (int) Pilgrim::query()->sum('paid_amount'),
        ];

        Pilgrim::query()->get(['id', 'full_name', 'package_price', 'paid_amount', 'departure_id'])->each(function (Pilgrim $pilgrim) use (&$stats) {
            if ((int) $pilgrim->package_price <= 0) {
                return;
            }

            if ($pilgrim->hasOverpayment()) {
                $stats['pilgrims_overpaid']++;
                $stats['total_overpayment'] += $pilgrim->overpaymentAmount();
            }

            if ($pilgrim->isPaidInFull()) {
                $stats['pilgrims_lunas']++;
            } elseif ((int) $pilgrim->paid_amount <= 0) {
                $stats['pilgrims_belum_bayar']++;
            } else {
                $stats['pilgrims_cicilan']++;
            }
        });

        Room::query()->withCount('pilgrims')->chunk(100, function ($rooms) use (&$stats) {
            foreach ($rooms as $room) {
                if ($room->isFull()) {
                    $stats['rooms_full']++;
                } elseif ($room->pilgrims_count > 0) {
                    $stats['rooms_incomplete']++;
                } else {
                    $stats['rooms_incomplete']++;
                }
            }
        });

        $incompleteRooms = Room::query()
            ->with(['departure:id,program_name,departure_date'])
            ->withCount('pilgrims')
            ->orderBy('room_number')
            ->get()
            ->filter(fn (Room $room) => ! $room->isFull())
            ->sortBy(fn (Room $room) => [
                $room->departure?->program_name ?? '',
                $room->room_number,
            ])
            ->values();

        $overpaidPilgrims = Pilgrim::query()
            ->with(['departure:id,program_name'])
            ->where('package_price', '>', 0)
            ->whereColumn('paid_amount', '>', 'package_price')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'departure_id', 'package_price', 'paid_amount']);

        return view('admin.operations.dashboard', [
            'stats' => $stats,
            'incompleteRooms' => $incompleteRooms,
            'overpaidPilgrims' => $overpaidPilgrims,
            'departures' => $departures,
        ]);
    }
}

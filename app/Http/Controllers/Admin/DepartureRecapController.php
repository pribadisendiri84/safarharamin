<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;

class DepartureRecapController extends Controller
{
    public function show(Departure $departure)
    {
        $departure->load([
            'rooms' => fn ($q) => $q->with(['pilgrims' => fn ($p) => $p->orderBy('full_name')])->orderBy('room_number'),
            'pilgrims' => fn ($q) => $q->with('room')->orderBy('full_name'),
        ]);

        return view('admin.operations.recap.show', [
            'departure' => $departure,
            'stats' => $departure->stats(),
        ]);
    }
}

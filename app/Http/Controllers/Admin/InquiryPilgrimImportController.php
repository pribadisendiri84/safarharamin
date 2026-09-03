<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomType;
use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Inquiry;
use App\Services\InquiryPilgrimImportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryPilgrimImportController extends Controller
{
    public function store(Request $request, Inquiry $inquiry, InquiryPilgrimImportService $importService)
    {
        $this->authorizeInquiry($inquiry);

        if (! $inquiry->isSold()) {
            return back()->with('err', 'Pengajuan harus berstatus Closing sebelum dipindah ke jamaah.');
        }

        if ($inquiry->pilgrimsImported()) {
            return redirect()
                ->route('admin.operations.pilgrims.index', ['departure_id' => $inquiry->pilgrims()->value('departure_id')])
                ->with('ok', 'Pengajuan ini sudah dipindah ke jamaah.');
        }

        $pax = $inquiry->soldPaxCount();

        $data = $request->validate([
            'departure_id' => ['required', 'exists:departures,id'],
            'room_type' => ['required', Rule::in(array_keys(RoomType::labels()))],
            'names' => ['required', 'array', 'size:'.$pax],
            'names.*' => ['required', 'string', 'max:180'],
        ]);

        $departure = Departure::query()->findOrFail($data['departure_id']);
        $pilgrims = $importService->import($inquiry, $departure, $data['room_type'], $data['names']);

        $message = $pilgrims->count() === 1
            ? '1 jamaah berhasil dipindah dari pengajuan closing.'
            : $pilgrims->count().' jamaah berhasil dipindah dari pengajuan closing.';

        return redirect()
            ->route('admin.operations.pilgrims.index', ['departure_id' => $departure->id])
            ->with('ok', $message);
    }

    private function authorizeInquiry(Inquiry $inquiry): void
    {
        if (! $inquiry->isVisibleTo(request()->user())) {
            throw new AuthorizationException;
        }
    }
}

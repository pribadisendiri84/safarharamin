<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomType;
use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Pilgrim;
use App\Models\PilgrimTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PilgrimController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Pilgrim::query()->with(['departure', 'room'])->latest(),
            $request
        );

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('full_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('haji_registration_id', 'like', "%{$q}%")
                    ->orWhere('haji_portion_number', 'like', "%{$q}%");
            });
        }

        if ($departureId = $request->integer('departure_id')) {
            $query->where('departure_id', $departureId);
        }

        if ($kind = $request->string('kind')->toString()) {
            if (array_key_exists($kind, Departure::KINDS)) {
                $query->whereHas('departure', fn ($builder) => $builder->where('program_kind', $kind));
            }
        }

        if ($roomType = $request->string('room_type')->toString()) {
            $query->where('room_type', $roomType);
        }

        if ($group = $request->string('group')->toString()) {
            if ($group === 'grouped') {
                $query->whereNotNull('room_id');
            } elseif ($group === 'ungrouped') {
                $query->whereNull('room_id');
            }
        }

        if ($payment = $request->string('payment')->toString()) {
            match ($payment) {
                'lunas' => $query
                    ->where('package_price', '>', 0)
                    ->whereColumn('paid_amount', '>=', 'package_price'),
                'belum' => $query->where('paid_amount', '<=', 0),
                'cicilan' => $query
                    ->where('package_price', '>', 0)
                    ->where('paid_amount', '>', 0)
                    ->whereColumn('paid_amount', '<', 'package_price'),
                default => null,
            };
        }

        return view('admin.operations.pilgrims.index', [
            'pilgrims' => $query->paginate(30)->withQueryString(),
            'departures' => Departure::query()->orderBy('program_kind')->orderBy('program_name')->get(['id', 'program_name', 'program_kind', 'departure_date']),
            ...$this->trashViewData(Pilgrim::class, $request),
        ]);
    }

    public function create(Request $request)
    {
        $departureId = $request->integer('departure_id') ?: null;

        if (! $departureId && ($kind = $request->string('kind')->toString()) && array_key_exists($kind, Departure::KINDS)) {
            $departureId = Departure::query()
                ->where('program_kind', $kind)
                ->orderBy('program_name')
                ->value('id');
        }

        return view('admin.operations.pilgrims.form', [
            'pilgrim' => new Pilgrim([
                'departure_id' => $departureId,
                'room_type' => 'quad',
            ]),
            'departures' => $departures = Departure::query()
                ->orderBy('program_kind')
                ->orderBy('program_name')
                ->get(['id', 'program_name', 'program_kind', 'departure_date', 'airline', 'flight_number', 'hotel_makkah', 'hotel_madinah', 'hotel_transit', 'hotel_maktab']),
            'departureInfos' => $this->departureInfos($departures),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $pilgrim = Pilgrim::query()->create($data);

        return redirect()
            ->route('admin.operations.pilgrims.show', $pilgrim)
            ->with('ok', 'Jamaah berhasil ditambahkan.');
    }

    public function show(Pilgrim $pilgrim)
    {
        $pilgrim->load(['departure', 'room', 'transactions.author']);

        return view('admin.operations.pilgrims.show', [
            'pilgrim' => $pilgrim,
            'departures' => Departure::query()->orderBy('program_kind')->orderBy('program_name')->get(['id', 'program_name', 'program_kind', 'departure_date', 'hotel_transit', 'hotel_maktab']),
        ]);
    }

    public function edit(Pilgrim $pilgrim)
    {
        $pilgrim->load('departure');

        $departures = Departure::query()
            ->orderBy('program_kind')
            ->orderBy('program_name')
            ->get(['id', 'program_name', 'program_kind', 'departure_date', 'airline', 'flight_number', 'hotel_makkah', 'hotel_madinah', 'hotel_transit', 'hotel_maktab']);

        return view('admin.operations.pilgrims.form', [
            'pilgrim' => $pilgrim,
            'departures' => $departures,
            'departureInfos' => $this->departureInfos($departures),
        ]);
    }

    public function update(Request $request, Pilgrim $pilgrim)
    {
        $data = $this->validated($request, $pilgrim);

        if ($pilgrim->room_id) {
            $room = $pilgrim->room;
            if ($room && ($data['room_type'] !== $pilgrim->room_type || (int) $data['departure_id'] !== (int) $pilgrim->departure_id)) {
                return back()
                    ->withInput()
                    ->with('err', 'Keluarkan jamaah dari room terlebih dahulu sebelum mengubah keberangkatan atau tipe kamar.');
            }
        }

        $pilgrim->update($data);

        return redirect()
            ->route('admin.operations.pilgrims.show', $pilgrim)
            ->with('ok', 'Data jamaah diperbarui.');
    }

    public function destroy(Pilgrim $pilgrim)
    {
        $pilgrim->delete();

        return redirect()
            ->route('admin.operations.pilgrims.index')
            ->with('ok', 'Jamaah dihapus.');
    }

    public function restore(int $pilgrim)
    {
        Pilgrim::query()->onlyTrashed()->findOrFail($pilgrim)->restore();

        return redirect()
            ->route('admin.operations.pilgrims.index', ['trashed' => 1])
            ->with('ok', 'Jamaah dipulihkan.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Pilgrim $pilgrim = null): array
    {
        $departure = Departure::query()->find($request->integer('departure_id'));
        $programKind = $departure?->program_kind ?? $pilgrim?->departure?->program_kind;

        $rules = [
            'departure_id' => ['required', 'exists:departures,id'],
            'full_name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', Rule::in(array_keys(Pilgrim::GENDERS))],
            'room_type' => ['required', Rule::in(array_keys(RoomType::labelsFor($programKind)))],
            'package_price' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'haji_registration_id' => ['nullable', 'string', 'max:120'],
            'haji_portion_number' => ['nullable', 'string', 'max:120'],
        ];

        return $request->validate($rules);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Departure>  $departures
     * @return array<int, array<string, mixed>>
     */
    private function departureInfos($departures): array
    {
        return $departures->mapWithKeys(fn (Departure $departure) => [
            $departure->id => [
                'program_kind' => $departure->program_kind,
                'airline' => $departure->airline,
                'flight_number' => $departure->flight_number,
                'hotel_makkah' => $departure->hotel_makkah,
                'hotel_madinah' => $departure->hotel_madinah,
                'hotel_transit' => $departure->hotel_transit,
                'hotel_maktab' => $departure->hotel_maktab,
                'edit_url' => route('admin.operations.departures.edit', $departure),
            ],
        ])->all();
    }
}

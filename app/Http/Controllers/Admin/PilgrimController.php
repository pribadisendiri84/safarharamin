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
            'departures' => Departure::query()->orderBy('program_name')->get(['id', 'program_name', 'departure_date']),
            ...$this->trashViewData(Pilgrim::class, $request),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.operations.pilgrims.form', [
            'pilgrim' => new Pilgrim([
                'departure_id' => $request->integer('departure_id') ?: null,
                'room_type' => 'quad',
            ]),
            'departures' => Departure::query()->orderBy('program_name')->get(['id', 'program_name', 'program_kind', 'departure_date']),
        ]);
    }

    public function store(Request $request)
    {
        $pilgrim = Pilgrim::query()->create($this->validated($request));

        return redirect()
            ->route('admin.operations.pilgrims.show', $pilgrim)
            ->with('ok', 'Jamaah berhasil ditambahkan.');
    }

    public function show(Pilgrim $pilgrim)
    {
        $pilgrim->load(['departure', 'room', 'transactions.author']);

        return view('admin.operations.pilgrims.show', [
            'pilgrim' => $pilgrim,
            'departures' => Departure::query()->orderBy('program_name')->get(['id', 'program_name', 'program_kind']),
        ]);
    }

    public function edit(Pilgrim $pilgrim)
    {
        return view('admin.operations.pilgrims.form', [
            'pilgrim' => $pilgrim,
            'departures' => Departure::query()->orderBy('program_name')->get(['id', 'program_name', 'program_kind', 'departure_date']),
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
        $isHaji = $departure?->isHaji() ?? ($pilgrim?->departure?->isHaji() ?? false);

        $rules = [
            'departure_id' => ['required', 'exists:departures,id'],
            'full_name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', Rule::in(array_keys(Pilgrim::GENDERS))],
            'room_type' => ['required', Rule::in(array_keys(RoomType::labels()))],
            'package_price' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'haji_registration_id' => [$isHaji ? 'nullable' : 'nullable', 'string', 'max:120'],
            'haji_portion_number' => ['nullable', 'string', 'max:120'],
        ];

        return $request->validate($rules);
    }
}

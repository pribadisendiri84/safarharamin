<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomType;
use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Pilgrim;
use App\Models\Room;
use App\Services\RoomGroupingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomGroupingController extends Controller
{
    public function __construct(private RoomGroupingService $grouping) {}

    public function index(Request $request, Departure $departure)
    {
        $activeTab = $request->string('tab')->toString();
        $roomTypes = RoomType::labelsFor($departure->program_kind);
        if (! array_key_exists($activeTab, $roomTypes)) {
            $activeTab = array_key_first($roomTypes);
        }

        $departure->load([
            'rooms' => fn ($q) => $q->with(['pilgrims' => fn ($p) => $p->orderBy('full_name')])->orderBy('room_number'),
            'pilgrims' => fn ($q) => $q->whereNull('room_id')->orderBy('full_name'),
        ]);

        $rooms = $departure->rooms->where('room_type', $activeTab)->values();
        $ungrouped = $departure->pilgrims->where('room_type', $activeTab)->values();

        return view('admin.operations.grouping.index', [
            'departure' => $departure,
            'activeTab' => $activeTab,
            'rooms' => $rooms,
            'ungrouped' => $ungrouped,
            'stats' => $departure->stats(),
            'roomTypes' => $roomTypes,
        ]);
    }

    public function autoGroup(Departure $departure)
    {
        $result = $this->grouping->autoGroup($departure);

        return back()->with('ok', "Auto group selesai. {$result['pilgrims_grouped']} jamaah dikelompokkan, {$result['rooms_created']} room baru dibuat.");
    }

    public function storeRoom(Request $request, Departure $departure)
    {
        $data = $request->validate([
            'room_type' => ['required', Rule::in(array_keys(RoomType::labelsFor($departure->program_kind)))],
        ]);

        $room = $this->grouping->createRoom($departure, RoomType::fromValue($data['room_type']));

        return back()->with('ok', "Room {$room->room_number} dibuat.");
    }

    public function assign(Request $request, Departure $departure)
    {
        $data = $request->validate([
            'pilgrim_id' => ['required', 'exists:pilgrims,id'],
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $pilgrim = $this->findPilgrim($departure, (int) $data['pilgrim_id']);
        $room = $this->findRoom($departure, (int) $data['room_id']);

        try {
            $this->grouping->assignPilgrim($pilgrim, $room);
        } catch (ValidationException $e) {
            return back()->with('err', collect($e->errors())->flatten()->first());
        }

        return back()->with('ok', "{$pilgrim->full_name} ditambahkan ke {$room->room_number}.");
    }

    public function move(Request $request, Departure $departure)
    {
        $data = $request->validate([
            'pilgrim_id' => ['required', 'exists:pilgrims,id'],
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $pilgrim = $this->findPilgrim($departure, (int) $data['pilgrim_id']);
        $room = $this->findRoom($departure, (int) $data['room_id']);

        try {
            $this->grouping->movePilgrim($pilgrim, $room);
        } catch (ValidationException $e) {
            return back()->with('err', collect($e->errors())->flatten()->first());
        }

        return back()->with('ok', "{$pilgrim->full_name} dipindah ke {$room->room_number}.");
    }

    public function remove(Request $request, Departure $departure)
    {
        $data = $request->validate([
            'pilgrim_id' => ['required', 'exists:pilgrims,id'],
        ]);

        $pilgrim = $this->findPilgrim($departure, (int) $data['pilgrim_id']);
        $this->grouping->removePilgrim($pilgrim);

        return back()->with('ok', "{$pilgrim->full_name} dikeluarkan dari room.");
    }

    public function destroyRoom(Departure $departure, Room $room)
    {
        $this->findRoom($departure, $room->id);

        try {
            $this->grouping->deleteRoom($room);
        } catch (ValidationException $e) {
            return back()->with('err', collect($e->errors())->flatten()->first());
        }

        return back()->with('ok', 'Room dihapus.');
    }

    private function findPilgrim(Departure $departure, int $id): Pilgrim
    {
        return Pilgrim::query()
            ->where('departure_id', $departure->id)
            ->findOrFail($id);
    }

    private function findRoom(Departure $departure, int $id): Room
    {
        return Room::query()
            ->where('departure_id', $departure->id)
            ->findOrFail($id);
    }
}

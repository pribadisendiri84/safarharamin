<?php

namespace App\Services;

use App\Enums\RoomType;
use App\Models\Departure;
use App\Models\Pilgrim;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomGroupingService
{
    public function createRoom(Departure $departure, RoomType $type): Room
    {
        return Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => $type->value,
            'room_number' => $this->nextRoomNumber($departure, $type),
            'capacity' => $type->capacity(),
        ]);
    }

    public function deleteRoom(Room $room): void
    {
        if ($room->pilgrims()->exists()) {
            throw ValidationException::withMessages([
                'room' => 'Room masih berisi jamaah. Pindahkan atau keluarkan jamaah terlebih dahulu.',
            ]);
        }

        $room->delete();
    }

    public function assignPilgrim(Pilgrim $pilgrim, Room $room): void
    {
        if ($pilgrim->room_id) {
            throw ValidationException::withMessages([
                'pilgrim' => 'Jamaah sudah berada di room. Gunakan pindah room jika perlu.',
            ]);
        }

        $this->assertSameDeparture($pilgrim, $room);
        $this->assertRoomTypeMatches($pilgrim, $room);
        $this->assertRoomHasCapacity($room);

        $pilgrim->update(['room_id' => $room->id]);
    }

    public function movePilgrim(Pilgrim $pilgrim, Room $room): void
    {
        if ($pilgrim->room_id === $room->id) {
            return;
        }

        $this->assertSameDeparture($pilgrim, $room);
        $this->assertRoomTypeMatches($pilgrim, $room);

        $occupants = $room->pilgrims()->where('id', '!=', $pilgrim->id)->count();
        if ($occupants >= $room->capacity) {
            throw ValidationException::withMessages([
                'room' => 'Room sudah penuh.',
            ]);
        }

        $pilgrim->update(['room_id' => $room->id]);
    }

    public function removePilgrim(Pilgrim $pilgrim): void
    {
        $pilgrim->update(['room_id' => null]);
    }

    /**
     * @return array{rooms_created: int, pilgrims_grouped: int}
     */
    public function autoGroup(Departure $departure): array
    {
        return DB::transaction(function () use ($departure) {
            $roomsCreated = 0;
            $pilgrimsGrouped = 0;

            $ungrouped = $departure->pilgrims()
                ->whereNull('room_id')
                ->orderBy('full_name')
                ->get()
                ->groupBy('room_type');

            foreach (RoomType::cases() as $type) {
                /** @var Collection<int, Pilgrim> $queue */
                $queue = collect($ungrouped->get($type->value, collect()))->values();

                if ($queue->isEmpty()) {
                    continue;
                }

                $openRooms = $departure->rooms()
                    ->where('room_type', $type->value)
                    ->withCount('pilgrims')
                    ->orderBy('room_number')
                    ->get()
                    ->filter(fn (Room $room) => ! $room->isFull());

                foreach ($openRooms as $room) {
                    while ($queue->isNotEmpty() && ! $room->isFull()) {
                        $this->assignPilgrim($queue->shift(), $room);
                        $pilgrimsGrouped++;
                    }
                }

                while ($queue->isNotEmpty()) {
                    $room = $this->createRoom($departure, $type);
                    $roomsCreated++;

                    while ($queue->isNotEmpty() && ! $room->isFull()) {
                        $this->assignPilgrim($queue->shift(), $room->fresh());
                        $pilgrimsGrouped++;
                    }
                }
            }

            return [
                'rooms_created' => $roomsCreated,
                'pilgrims_grouped' => $pilgrimsGrouped,
            ];
        });
    }

    public function nextRoomNumber(Departure $departure, RoomType $type): string
    {
        $prefix = $type->prefix().'-';
        $max = $departure->rooms()
            ->where('room_type', $type->value)
            ->where('room_number', 'like', $prefix.'%')
            ->pluck('room_number')
            ->map(function (string $number) use ($prefix) {
                $suffix = substr($number, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            })
            ->max() ?? 0;

        return $type->prefix().'-'.sprintf('%02d', $max + 1);
    }

    private function assertSameDeparture(Pilgrim $pilgrim, Room $room): void
    {
        if ($pilgrim->departure_id !== $room->departure_id) {
            throw ValidationException::withMessages([
                'room' => 'Room tidak sesuai keberangkatan jamaah.',
            ]);
        }
    }

    private function assertRoomTypeMatches(Pilgrim $pilgrim, Room $room): void
    {
        if ($pilgrim->room_type !== $room->room_type) {
            throw ValidationException::withMessages([
                'room' => 'Tipe kamar jamaah tidak sesuai room.',
            ]);
        }
    }

    private function assertRoomHasCapacity(Room $room): void
    {
        if ($room->isFull()) {
            throw ValidationException::withMessages([
                'room' => 'Room sudah penuh.',
            ]);
        }
    }
}

<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'package_id',
    'program_name',
    'program_kind',
    'departure_date',
    'airline',
    'flight_number',
    'hotel_makkah',
    'hotel_madinah',
    'notes',
])]
class Departure extends Model
{
    use RecordsActivity, SoftDeletes;

    public const KINDS = [
        'umroh' => 'Umroh',
        'haji' => 'Haji',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function pilgrims(): HasMany
    {
        return $this->hasMany(Pilgrim::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function isHaji(): bool
    {
        return $this->program_kind === 'haji';
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->program_kind] ?? $this->program_kind;
    }

    public function formattedDepartureDate(): string
    {
        return $this->departure_date?->translatedFormat('d M Y') ?? 'Jadwal menyusul';
    }

    /** @return array<string, int|float> */
    public function stats(): array
    {
        $pilgrims = $this->pilgrims()->get(['id', 'room_id', 'room_type']);
        $rooms = $this->rooms()->withCount('pilgrims')->get();

        $grouped = $pilgrims->whereNotNull('room_id')->count();
        $ungrouped = $pilgrims->whereNull('room_id')->count();

        $fullRooms = $rooms->filter(fn (Room $room) => $room->isFull())->count();
        $partialRooms = $rooms->filter(fn (Room $room) => ! $room->isFull() && $room->pilgrims_count > 0)->count();
        $emptyRooms = $rooms->filter(fn (Room $room) => $room->pilgrims_count === 0)->count();

        return [
            'total_pilgrims' => $pilgrims->count(),
            'total_quad' => $pilgrims->where('room_type', 'quad')->count(),
            'total_triple' => $pilgrims->where('room_type', 'triple')->count(),
            'total_double' => $pilgrims->where('room_type', 'double')->count(),
            'total_rooms' => $rooms->count(),
            'rooms_full' => $fullRooms,
            'rooms_partial' => $partialRooms,
            'rooms_empty' => $emptyRooms,
            'rooms_incomplete' => $partialRooms + $emptyRooms,
            'pilgrims_grouped' => $grouped,
            'pilgrims_ungrouped' => $ungrouped,
        ];
    }
}

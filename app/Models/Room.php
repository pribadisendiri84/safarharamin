<?php

namespace App\Models;

use App\Enums\RoomType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'departure_id',
    'room_type',
    'room_number',
    'capacity',
])]
class Room extends Model
{
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $room): void {
            if (! $room->capacity) {
                $room->capacity = RoomType::capacityFor($room->room_type);
            }
        });
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function pilgrims(): HasMany
    {
        return $this->hasMany(Pilgrim::class);
    }

    public function roomTypeEnum(): RoomType
    {
        return RoomType::fromValue($this->room_type);
    }

    public function typeLabel(): string
    {
        return $this->roomTypeEnum()->label();
    }

    public function occupantsCount(): int
    {
        return $this->pilgrims()->count();
    }

    public function isFull(): bool
    {
        return $this->occupantsCount() >= $this->capacity;
    }

    public function isEmpty(): bool
    {
        return $this->occupantsCount() === 0;
    }

    public function remainingSlots(): int
    {
        return max(0, $this->capacity - $this->occupantsCount());
    }

    public function statusLabel(): string
    {
        if ($this->isFull()) {
            return 'FULL';
        }

        if ($this->occupantsCount() === 0) {
            return 'KOSONG';
        }

        return 'BELUM PENUH';
    }

    public function statusClass(): string
    {
        if ($this->isFull()) {
            return 'ok';
        }

        if ($this->occupantsCount() === 0) {
            return 'muted';
        }

        return 'warn';
    }

    public function occupancyLine(): string
    {
        return $this->occupantsCount().' / '.$this->capacity;
    }
}

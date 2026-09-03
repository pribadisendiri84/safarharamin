<?php

namespace App\Enums;

enum RoomType: string
{
    case Quad = 'quad';
    case Triple = 'triple';
    case Double = 'double';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Quad => 'Quad',
            self::Triple => 'Triple',
            self::Double => 'Double',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Quad => 'Q',
            self::Triple => 'T',
            self::Double => 'D',
        };
    }

    public function capacity(): int
    {
        return match ($this) {
            self::Quad => 4,
            self::Triple => 3,
            self::Double => 2,
        };
    }

    public static function fromValue(string $value): self
    {
        return self::from($value);
    }

    public static function capacityFor(string $value): int
    {
        return self::fromValue($value)->capacity();
    }
}

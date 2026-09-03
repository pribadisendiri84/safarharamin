<?php

namespace App\Enums;

enum RoomType: string
{
    case Quad = 'quad';
    case Triple = 'triple';
    case Double = 'double';
    case DoublePlus = 'double_plus';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function labelsFor(?string $programKind = null): array
    {
        $labels = self::labels();

        if ($programKind === 'haji') {
            return $labels;
        }

        unset($labels[self::DoublePlus->value]);

        return $labels;
    }

    public function label(): string
    {
        return match ($this) {
            self::Quad => 'Quad',
            self::Triple => 'Triple',
            self::Double => 'Double',
            self::DoublePlus => 'Double Plus',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Quad => 'Q',
            self::Triple => 'T',
            self::Double => 'D',
            self::DoublePlus => 'DP',
        };
    }

    public function capacity(): int
    {
        return match ($this) {
            self::Quad => 4,
            self::Triple => 3,
            self::Double, self::DoublePlus => 2,
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

<?php

namespace App\Enums;

enum Shifts: string
{
    case MORNING = 'Morning';
    case EVENING = 'Evening';
    case NIGHT = 'Night';

    public function label(): string
    {
        return match ($this) {
            self::MORNING => 'Morning',
            self::EVENING => 'Evening',
            self::NIGHT => 'Night',
        };
    }

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}

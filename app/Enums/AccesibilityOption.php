<?php

namespace App\Enums;

enum AccesibilityOption: string
{
    case NORMAL = 'Normal';
    case WHEELCHAIR_ACCESS = 'Wheelchair Access';
    case PET_FRIENDLY_RIDE = 'Pet-Friendly Ride';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::WHEELCHAIR_ACCESS => 'Wheelchair Access',
            self::PET_FRIENDLY_RIDE => 'Pet-Friendly Ride',
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

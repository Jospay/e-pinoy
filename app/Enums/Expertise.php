<?php

namespace App\Enums;

enum Expertise: string
{
    case MECHANICAL = 'Mechanical';
    case ELECTRICAL = 'Electrical';
    case BATTERY = 'Battery';

    public function label(): string
    {
        return match ($this) {
            self::MECHANICAL => 'Mechanical',
            self::ELECTRICAL => 'Electrical',
            self::BATTERY => 'Battery',
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

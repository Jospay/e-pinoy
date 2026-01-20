<?php

namespace App\Enums;

enum Language: string
{
    case ENGLISH = 'English';
    case FILIPINO = 'Filipino';
    case OTHERS = 'Others';

    public function label(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::FILIPINO => 'Filipino',
            self::OTHERS => 'Others',
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

<?php

namespace App\Enums;

enum IdType: string
{
    case NATIONAL_ID = 'National ID';
    case PASSPORT = 'Passport';
    case DRIVER_LICENSE = 'Driver License';
    case VOTER_ID = 'Voter ID';
    case UMID = 'Unified Multi-Purpose ID';
    case TIN_ID = 'TIN ID';

    public function label(): string
    {
        return match ($this) {
            self::NATIONAL_ID => 'National ID',
            self::PASSPORT => 'Passport',
            self::DRIVER_LICENSE => 'Driver License',
            self::VOTER_ID => 'Voter ID',
            self::UMID => 'Unified Multi-Purpose ID',
            self::TIN_ID => 'TIN ID',
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

<?php

namespace App\Enums;

enum TipeIndikator: string
{
    case Capaian = 'capaian';
    case Perilaku = 'perilaku';
    case Dimensi = 'dimensi';

    // Not on ERD/CD: presentation helpers for form selects, added during scaffolding.

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Capaian->value => 'Capaian',
            self::Perilaku->value => 'Perilaku',
            self::Dimensi->value => 'Dimensi',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}

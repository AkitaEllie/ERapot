<?php

namespace App\Enums;

enum JenisMataPelajaran: string
{
    case Intrakurikuler = 'intrakurikuler';
    case KoKurikuler = 'kokurikuler';
    case MuatanLokal = 'muatan_lokal';

    // Not on ERD/CD: presentation helpers for form selects, added during scaffolding.

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Intrakurikuler->value => 'Intrakurikuler',
            self::KoKurikuler->value => 'KoKurikuler',
            self::MuatanLokal->value => 'Muatan Lokal',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}

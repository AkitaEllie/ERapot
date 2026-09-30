<?php

namespace App\Enums;

enum Nilai: string
{
    case MulaiBerkembang = 'MB';
    case BerkembangSesuaiHarapan = 'BSH';
    case BerkembangSangatBaik = 'BSB';

    // Not on ERD/CD: presentation helpers for form selects, added during scaffolding.

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::MulaiBerkembang->value => 'Mulai Berkembang',
            self::BerkembangSesuaiHarapan->value => 'Berkembang Sesuai Harapan',
            self::BerkembangSangatBaik->value => 'Berkembang Sangat Baik',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}

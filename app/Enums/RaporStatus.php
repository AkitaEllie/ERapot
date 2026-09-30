<?php

namespace App\Enums;

enum RaporStatus: string
{
    case BelumDiisi = 'belum_diisi';
    case Draft = 'draft';
    case Menunggu = 'menunggu';
    case PerluRevisi = 'perlu_revisi';
    case Disetujui = 'disetujui';

    // Not on ERD/CD: presentation helpers for form selects, added during scaffolding.

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::BelumDiisi->value => 'Belum Diisi',
            self::Draft->value => 'Draft',
            self::Menunggu->value => 'Menunggu',
            self::PerluRevisi->value => 'Perlu Revisi',
            self::Disetujui->value => 'Disetujui',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }

    // Not on ERD/CD: Tailwind classes for status badges, added during scaffolding.

    public function badgeClasses(): string
    {
        return match ($this) {
            self::BelumDiisi => 'bg-sunken text-label ring-hairline',
            self::Draft => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Menunggu => 'bg-blue-50 text-blue-800 ring-blue-200',
            self::PerluRevisi => 'bg-rose-50 text-rose-800 ring-rose-200',
            self::Disetujui => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        };
    }
}

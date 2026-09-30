<?php

namespace App\Enums;

enum Jenjang: string
{
    case Kba = 'KB-A';
    case Kbb = 'KB-B';
    case Tka = 'TK-A';
    case Tkb = 'TK-B';

    // Not on ERD/CD: presentation helpers for form selects, added during scaffolding.

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Kba->value => 'KB-A',
            self::Kbb->value => 'KB-B',
            self::Tka->value => 'TK-A',
            self::Tkb->value => 'TK-B',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}

<?php

namespace App\Enums;

enum StatusNarasi: string
{
    case Draft = 'draft';
    case Diedit = 'diedit';
    case Final = 'final';

    // Not on ERD/CD: presentation helpers for form selects, added during scaffolding.

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Diedit->value => 'Diedit',
            self::Final->value => 'Final',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }

    // Not on ERD/CD: state helper added during scaffolding.

    public function isFinal(): bool
    {
        return $this === self::Final;
    }
}

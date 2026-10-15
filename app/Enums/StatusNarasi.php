<?php

namespace App\Enums;

enum StatusNarasi: string
{
    case Draft = 'draft';
    case Diedit = 'diedit';
    case Final = 'final';

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

    public function isFinal(): bool
    {
        return $this === self::Final;
    }
}

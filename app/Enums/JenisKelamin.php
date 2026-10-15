<?php

namespace App\Enums;

enum JenisKelamin: string
{
    case LakiLaki = 'L';
    case Perempuan = 'P';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::LakiLaki->value => 'Laki-laki',
            self::Perempuan->value => 'Perempuan',
        ];
    }

    public function label(): string
    {
        return self::options()[$this->value];
    }
}

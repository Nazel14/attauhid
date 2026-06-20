<?php

namespace App\Enums;

enum MetodeTarget: string
{
    case Quran = 'quran';
    case Iqro  = 'iqro';
    // Ke depan tinggal tambah: case Ummi = 'ummi'; dst.

    public function label(): string
    {
        return match ($this) {
            self::Quran => 'Al-Qur\'an (Juz)',
            self::Iqro  => 'Iqro (Jilid)',
        };
    }

    public function satuan(): string
    {
        return match ($this) {
            self::Quran => 'Juz',
            self::Iqro  => 'Jilid',
        };
    }

    /** Untuk dropdown form */
    public static function options(): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
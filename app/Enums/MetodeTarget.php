<?php

namespace App\Enums;

enum MetodeTarget: string
{
    case Quran = 'quran';
    case Iqro  = 'iqro';
    case Aisar = 'aisar';

    public function label(): string
    {
        return match ($this) {
            self::Quran => 'Al-Qur\'an (Juz)',
            self::Iqro  => 'Iqro (Jilid)',
            self::Aisar => 'Aisar (Jilid)',
        };
    }

    public function satuan(): string
    {
        return match ($this) {
            self::Quran => 'Juz',
            self::Iqro, self::Aisar => 'Jilid',
        };
    }

    /** true jika metode berbasis jilid+halaman (bukan mushaf) */
    public function isJilid(): bool
    {
        return $this !== self::Quran;
    }

    public static function options(): array
    {
        return array_map(fn($c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}

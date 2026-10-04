<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Srpsko formatiranje iznosa, datuma i množine (274.340,38 RSD).
 * Svi iznosi se interno vode u parama (int) da bi se izbegle greške zaokruživanja.
 */
class Fmt
{
    public const MONTHS = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'avg', 'sep', 'okt', 'nov', 'dec'];

    public const WEEKDAYS = ['Ne', 'Po', 'Ut', 'Sr', 'Če', 'Pe', 'Su'];

    public static function cents(string|int|float|null $decimal): int
    {
        return (int) round(((float) $decimal) * 100);
    }

    public static function num(int $cents): string
    {
        $neg = $cents < 0;
        $abs = abs($cents);

        return ($neg ? '−' : '').number_format(intdiv($abs, 100), 0, ',', '.').','.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function rsd(int $cents): string
    {
        return self::num($cents).' RSD';
    }

    /** Kratak zapis za bedževe i grafikon: 1,2k / 274k. */
    public static function short(int $cents): string
    {
        $n = $cents / 100;
        if ($n <= 0) {
            return '';
        }
        if ($n < 1000) {
            return (string) round($n);
        }
        if ($n >= 100000) {
            return round($n / 1000).'k';
        }

        return str_replace('.', ',', (string) (round($n / 100) / 10)).'k';
    }

    public static function date(?CarbonInterface $d): string
    {
        return $d ? $d->format('d.m.Y') : '—';
    }

    public static function month(CarbonInterface $d): string
    {
        return self::MONTHS[$d->month - 1];
    }

    public static function weekday(CarbonInterface $d): string
    {
        return self::WEEKDAYS[$d->dayOfWeek];
    }

    public static function dan(int $n): string
    {
        return ($n % 10 === 1 && $n % 100 !== 11) ? 'dan' : 'dana';
    }

    public static function fakt(int $n): string
    {
        if ($n === 1) {
            return '1 faktura';
        }
        $few = $n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 12 || $n % 100 > 14);

        return $n.($few ? ' fakture' : ' faktura');
    }

    public static function initials(?string $name): string
    {
        $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $out = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $out .= mb_strtoupper(mb_substr($w, 0, 1));
        }

        return $out !== '' ? $out : '?';
    }

    /** Prihvata "274.340,38", "274340.38", "274 340,38 RSD". Vraća pare ili null. */
    public static function parseAmount(?string $s): ?int
    {
        $s = preg_replace('/\s|RSD/iu', '', (string) $s);
        if (str_contains($s, ',')) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        }
        if (! is_numeric($s)) {
            return null;
        }

        return (int) round(((float) $s) * 100);
    }
}

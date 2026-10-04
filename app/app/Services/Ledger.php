<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Support\Fmt;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Računa saldo, rok i status svake fakture i zbirove po dobavljaču.
 * Status: kasni (rok prošao), uskoro (rok u narednih N dana), otvoreno, placeno.
 */
class Ledger
{
    public const PRIORITY = ['kasni' => 0, 'uskoro' => 1, 'otvoreno' => 2, 'placeno' => 3];

    public const LABELS = ['kasni' => 'Kasni', 'uskoro' => 'Uskoro dospeva', 'otvoreno' => 'Otvoreno', 'placeno' => 'Plaćeno'];

    public readonly CarbonImmutable $today;

    public readonly int $warnDays;

    /** @var Collection<int, object> */
    public readonly Collection $invoices;

    /** @var Collection<int, object> sortirano: status, pa dug, pa naziv */
    public readonly Collection $suppliers;

    public function __construct(?CarbonImmutable $today = null, ?int $warnDays = null)
    {
        $this->today = ($today ?? CarbonImmutable::today())->startOfDay();
        $this->warnDays = $warnDays ?? (int) config('dugovanja.upozorenje_dana', 7);

        $suppliers = Supplier::with(['invoices.payments', 'contacts'])->get();

        $all = collect();
        $aggs = collect();
        foreach ($suppliers as $s) {
            $infos = $s->invoices->map(fn (Invoice $i) => $this->invoiceInfo($i, $s));
            $all = $all->concat($infos);
            $aggs->push($this->aggregate($s, $infos));
        }

        $this->invoices = $all;
        $this->suppliers = $aggs->sort(fn ($a, $b) => [self::PRIORITY[$a->status], -$a->total, mb_strtolower($a->supplier->name)]
            <=> [self::PRIORITY[$b->status], -$b->total, mb_strtolower($b->supplier->name)])->values();
    }

    public function invoiceInfo(Invoice $inv, Supplier $s): object
    {
        $amount = Fmt::cents($inv->amount);
        $paid = $inv->payments->sum(fn ($p) => Fmt::cents($p->amount));
        $rem = max(0, $amount - $paid);
        $due = CarbonImmutable::parse($inv->due_on)->startOfDay();
        $d = (int) $this->today->diffInDays($due, false);
        $status = $rem <= 0 ? 'placeno' : ($d < 0 ? 'kasni' : ($d <= $this->warnDays ? 'uskoro' : 'otvoreno'));

        return (object) (compact('inv', 'amount', 'paid', 'rem', 'due', 'd', 'status') + ['supplier' => $s]);
    }

    private function aggregate(Supplier $s, Collection $infos): object
    {
        $sum = fn (callable $f) => $infos->filter($f)->sum('rem');
        $total = $sum(fn ($i) => $i->rem > 0);
        $soon = $sum(fn ($i) => $i->status === 'uskoro');
        $over = $sum(fn ($i) => $i->status === 'kasni');
        $open = $infos->filter(fn ($i) => $i->rem > 0)->sortBy(fn ($i) => $i->due->format('Y-m-d'))->values();
        $status = $over > 0 ? 'kasni' : ($soon > 0 ? 'uskoro' : ($total > 0 ? 'otvoreno' : 'placeno'));

        return (object) [
            'supplier' => $s,
            'invoices' => $infos,
            'total' => $total,
            'soon' => $soon,
            'over' => $over,
            'later' => $total - $soon - $over,
            'next' => $open->first(),
            'openCount' => $open->count(),
            'status' => $status,
        ];
    }

    public function find(int $supplierId): ?object
    {
        return $this->suppliers->first(fn ($a) => $a->supplier->id === $supplierId);
    }

    public function open(): Collection
    {
        return $this->invoices->filter(fn ($i) => $i->rem > 0);
    }

    public function totals(): array
    {
        $total = $this->suppliers->sum('total');
        $soon = $this->suppliers->sum('soon');
        $over = $this->suppliers->sum('over');
        $open = $this->open();

        return [
            'total' => $total, 'soon' => $soon, 'over' => $over, 'later' => $total - $soon - $over,
            'overCount' => $open->where('status', 'kasni')->count(),
            'soonCount' => $open->where('status', 'uskoro')->count(),
            'laterCount' => $open->where('status', 'otvoreno')->count(),
            'debtSuppliers' => $this->suppliers->filter(fn ($a) => $a->total > 0)->count(),
        ];
    }

    /** Fakture koje kasne ili dospevaju u narednih N dana, po roku. */
    public function alerts(?int $withinDays = null): Collection
    {
        $within = $withinDays ?? $this->warnDays;

        return $this->open()->filter(fn ($i) => $i->d <= $within)
            ->sortBy(fn ($i) => $i->due->format('Y-m-d').'-'.str_pad((string) $i->inv->id, 10, '0', STR_PAD_LEFT))->values();
    }

    /** Kolone grafikona "Rokovi plaćanja": jedna za sve što kasni, pa po jedna za danas..danas+N. */
    public function timeline(int $days = 14): array
    {
        $open = $this->open();
        $over = $open->filter(fn ($i) => $i->d < 0);
        $cols = [['k' => -1, 'sum' => $over->sum('rem'), 'list' => $over->values(), 'day' => '!', 'wd' => 'kasni']];
        for ($k = 0; $k <= $days; $k++) {
            $date = $this->today->addDays($k);
            $list = $open->filter(fn ($i) => $i->d === $k)->values();
            $cols[] = ['k' => $k, 'sum' => $list->sum('rem'), 'list' => $list, 'day' => (string) $date->day, 'wd' => Fmt::weekday(Carbon::instance($date))];
        }
        $max = max(1, ...array_column($cols, 'sum'));
        foreach ($cols as &$c) {
            $c['pct'] = $c['sum'] > 0 ? max(8, $c['sum'] / $max * 100) : 0;
            $c['tone'] = $c['k'] < 0 ? 'kasni' : ($c['k'] <= $this->warnDays ? 'uskoro' : 'otvoreno');
        }

        return $cols;
    }

    /** Opis roka za prikaz: "kasni 3 dana", "dospeva danas", "za 5 dana". */
    public static function daysText(object $i): string
    {
        return match (true) {
            $i->status === 'placeno' => 'izmireno',
            $i->d < 0 => 'kasni '.(-$i->d).' '.Fmt::dan(-$i->d),
            $i->d === 0 => 'dospeva danas',
            $i->d === 1 => 'dospeva sutra',
            default => 'za '.$i->d.' '.Fmt::dan($i->d),
        };
    }

    /** Segmenti trake kasni / uskoro / kasnije (u relativnim težinama). */
    public static function segments(int $over, int $soon, int $later): array
    {
        if ($over + $soon + $later <= 0) {
            return [['tone' => 'empty', 'w' => 1]];
        }

        return array_values(array_filter([
            ['tone' => 'kasni', 'w' => $over],
            ['tone' => 'uskoro', 'w' => $soon],
            ['tone' => 'otvoreno', 'w' => $later],
        ], fn ($s) => $s['w'] > 0));
    }
}

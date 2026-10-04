@extends('layouts.app')
@section('title', 'Pregled')

@php
    use App\Services\Ledger;
    use App\Support\Fmt;
    $W = $ledger->warnDays;
    $t = $ledger->totals();
    $alerts = $ledger->alerts();
    $supCount = $ledger->suppliers->count();
    $tiles = $ledger->suppliers->map(fn ($a) => ['name' => $a->supplier->name, 'status' => $a->status])->values();
    $filters = ['svi' => 'Svi', 'kasni' => 'Kasni', 'uskoro' => 'Uskoro', 'otvoreno' => 'Otvoreno', 'placeno' => 'Plaćeno'];
@endphp

@section('content')
<div class="top-grid">
  <section class="glass hero">
    <div class="hero-head">
      <div>
        <div class="label">Ukupno zaduženje · {{ Fmt::date($ledger->today) }}</div>
        <div class="big">{{ Fmt::num($t['total']) }}<span class="cur">RSD</span></div>
      </div>
      <div class="chip-info">{{ $t['debtSuppliers'] }} od {{ $supCount }} dobavljača sa dugom</div>
    </div>
    <div class="segs">
      @foreach (Ledger::segments($t['over'], $t['soon'], $t['later']) as $g)
        <div class="seg seg-{{ $g['tone'] }}" style="flex:{{ $g['w'] }} 1 0"></div>
      @endforeach
    </div>
    <div class="legend">
      <div>
        <div class="legend-h fg-kasni"><div class="dot bg-kasni"></div>Kasni · {{ Fmt::fakt($t['overCount']) }}</div>
        <div class="legend-v">{{ Fmt::rsd($t['over']) }}</div>
      </div>
      <div>
        <div class="legend-h fg-uskoro"><div class="dot bg-uskoro"></div>Narednih {{ $W }} dana · {{ Fmt::fakt($t['soonCount']) }}</div>
        <div class="legend-v">{{ Fmt::rsd($t['soon']) }}</div>
      </div>
      <div>
        <div class="legend-h fg-soft"><div class="dot bg-otvoreno"></div>Kasnije · {{ Fmt::fakt($t['laterCount']) }}</div>
        <div class="legend-v">{{ Fmt::rsd($t['later']) }}</div>
      </div>
    </div>
  </section>

  <section class="glass chart">
    <div class="card-head">
      <div class="card-title">Rokovi plaćanja</div>
      <div class="card-sub">narednih <span class="tl-days-d">14</span><span class="tl-days-m">8</span> dana</div>
    </div>
    <div class="tl">
      @foreach ($ledger->timeline(14) as $c)
        @php
            $has = $c['sum'] > 0;
            $tip = $c['list']->map(fn ($i) => $i->supplier->name.': '.Fmt::rsd($i->rem))->implode("\n");
            $cls = ($c['k'] < 0 ? ' over' : '').($c['k'] === 0 ? ' today' : '').($c['k'] > 8 ? ' far' : '').($has ? ' has' : '');
        @endphp
        <{{ $has ? 'a' : 'div' }} class="tl-col{{ $cls }}" @if ($has) href="{{ route('suppliers.show', $c['list'][0]->supplier) }}" title="{{ $tip }}" @endif>
          <div class="tl-amt">{{ Fmt::short($c['sum']) }}</div>
          <div class="tl-barwrap"><div class="tl-bar {{ $has ? 't-'.$c['tone'] : '' }}" @if ($has) style="height:{{ round($c['pct'], 2) }}%" @endif></div></div>
          <div class="tl-day">{{ $c['day'] }}</div>
          <div class="tl-wd">{{ $c['wd'] }}</div>
        </{{ $has ? 'a' : 'div' }}>
      @endforeach
    </div>
  </section>
</div>

<div class="main-grid">
  <div style="min-width:0" x-data="home(@js($tiles))">
    <div class="toolbar">
      <div class="chips">
        <button type="button" class="chip dashed only-mobile" @click="openSupplier()">+ Dobavljač</button>
        @foreach ($filters as $k => $label)
          <button type="button" class="chip" :class="filter === '{{ $k }}' && 'on'" @click="filter = '{{ $k }}'">
            {{ $label }}<span>{{ $k === 'svi' ? $supCount : $ledger->suppliers->where('status', $k)->count() }}</span>
          </button>
        @endforeach
      </div>
      <label class="search">
        <input x-model="q" placeholder="Pretraži dobavljače" aria-label="Pretraži dobavljače">
      </label>
    </div>
    <div class="tiles">
      @foreach ($ledger->suppliers as $idx => $a)
        @php $st = $a->status; $ni = $a->next; @endphp
        <a href="{{ route('suppliers.show', $a->supplier) }}" class="glass tile st-{{ $st }}" x-show="match(@js($tiles[$idx]))">
          <div class="tile-head">
            <div class="avatar av-{{ $st }}">{{ Fmt::initials($a->supplier->name) }}</div>
            <div style="flex:1;min-width:0">
              <div class="tile-name ellipsis">{{ $a->supplier->name }}</div>
              <div class="tile-status fg-{{ $st }}">{{ $st === 'placeno' ? 'Sve izmireno' : Ledger::LABELS[$st].' · '.Fmt::fakt($a->openCount) }}</div>
            </div>
          </div>
          <div class="tile-amt">{{ Fmt::num($a->total) }}<span>RSD</span></div>
          <div class="segs sm">
            @foreach (Ledger::segments($a->over, $a->soon, $a->later) as $g)
              <div class="seg seg-{{ $g['tone'] }}" style="flex:{{ $g['w'] }} 1 0"></div>
            @endforeach
          </div>
          <div class="tile-foot">
            <div class="muted">{{ $ni ? 'Rok '.Fmt::date($ni->due) : 'Nema otvorenih faktura' }}</div>
            <div class="{{ $ni ? 'fg-'.$ni->status : 'fg-otvoreno' }}">{{ $ni ? Ledger::daysText($ni) : '' }}</div>
          </div>
        </a>
      @endforeach
    </div>
    <div class="empty" x-show="none" x-cloak>Nema dobavljača za ovaj filter.</div>
  </div>

  <aside class="panel">
    <div class="glass panel-card">
      <div class="panel-head">
        <div>
          <div class="card-title">Za plaćanje</div>
          <div class="card-sub" style="margin-top:2px">Kasni i narednih {{ $W }} dana</div>
        </div>
        <div class="count-badge">{{ $alerts->count() }}</div>
      </div>
      <div class="alerts">
        @forelse ($alerts as $i)
          @php $k = $i->status === 'kasni'; @endphp
          <a href="{{ route('suppliers.show', $i->supplier) }}" class="alert">
            <div class="alert-badge {{ $k ? 'kasni' : '' }}">
              <b>{{ abs($i->d) }}</b>
              <small>{{ $k ? 'kasni' : ($i->d === 0 ? 'danas' : Fmt::dan($i->d)) }}</small>
            </div>
            <div style="flex:1;min-width:0">
              <div class="alert-sup">{{ $i->supplier->name }}</div>
              <div class="alert-meta">rok {{ Fmt::date($i->due) }} · br. {{ $i->inv->number }}</div>
            </div>
            <div class="alert-amt">{{ Fmt::rsd($i->rem) }}</div>
          </a>
        @empty
          <div class="empty" style="padding:24px 10px">Nema faktura koje dospevaju.</div>
        @endforelse
      </div>
    </div>
    <div class="panel-note">
      <div>Podaci se čuvaju u bazi na serveru.</div>
      @if (config('dugovanja.demo'))
        <form method="POST" action="{{ route('demo.reset') }}" onsubmit="return confirm('Zameniti sve podatke demo podacima?')">
          @csrf
          <button class="link-btn">Vrati demo podatke</button>
        </form>
      @endif
    </div>
  </aside>
</div>
@endsection

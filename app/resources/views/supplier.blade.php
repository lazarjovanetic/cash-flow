@extends('layouts.app')
@section('title', $cur->supplier->name)

@php
    use App\Services\Ledger;
    use App\Support\Fmt;
    $W = $ledger->warnDays;
    $s = $cur->supplier;
    $term = $s->term_days;
    $byIssued = $cur->invoices->sortBy(fn ($i) => $i->inv->issued_on->format('Y-m-d').'-'.str_pad((string) $i->inv->id, 10, '0', STR_PAD_LEFT))->values();
    $byDue = $byIssued->sortBy(fn ($i) => $i->due->format('Y-m-d'))->values();
    $order = ['kasni' => 'Kasni', 'uskoro' => "Dospeva u {$W} dana", 'otvoreno' => 'Otvoreno', 'placeno' => 'Plaćeno'];
    $groups = collect($order)->map(fn ($title, $k) => [
        'key' => $k,
        'title' => $title,
        'items' => ($k === 'placeno' ? $byIssued->reverse() : $byDue)->where('status', $k)->values(),
    ])->filter(fn ($g) => $g['items']->isNotEmpty())->values();
    $invTotal = $byIssued->count();
    $activeCount = $byIssued->where('status', '!=', 'placeno')->count();
    $meta = collect([$s->pib ? "PIB {$s->pib}" : null, "Valuta {$term} dana", Fmt::fakt($cur->openCount).' otvoreno'])->filter()->implode(' · ');
@endphp

@section('content')
<section class="glass sup-hero">
  <div class="sup-id">
    <div class="sup-avatar">{{ Fmt::initials($s->name) }}</div>
    <div style="min-width:0">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 class="sup-name" style="margin:0">{{ $s->name }}</h1>
        <div class="status-pill av-{{ $cur->status }}">{{ Ledger::LABELS[$cur->status] }}</div>
      </div>
      <div class="sup-meta">{{ $meta }}</div>
    </div>
  </div>
  <div class="sup-debt">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap">
      <div>
        <div class="label" style="font-size:13px">Ukupan dug</div>
        <div class="sup-amt">{{ Fmt::num($cur->total) }}<span>RSD</span></div>
      </div>
      <button type="button" class="btn btn-primary btn-lg" @click="openInvoice({{ $s->id }})">+ Nova faktura</button>
    </div>
    <div class="segs md">
      @foreach (Ledger::segments($cur->over, $cur->soon, $cur->later) as $g)
        <div class="seg seg-{{ $g['tone'] }}" style="flex:{{ $g['w'] }} 1 0"></div>
      @endforeach
    </div>
    <div class="sup-legend">
      <div class="fg-kasni"><div class="dot bg-kasni"></div>Kasni {{ Fmt::rsd($cur->over) }}</div>
      <div class="fg-uskoro"><div class="dot bg-uskoro"></div>{{ $W }} dana {{ Fmt::rsd($cur->soon) }}</div>
      <div class="fg-soft"><div class="dot bg-otvoreno"></div>Kasnije {{ Fmt::rsd($cur->later) }}</div>
    </div>
  </div>
</section>

<div class="sup-grid">
  <div class="inv-col" x-data="{ hidePaid: true, expanded: null }">
    <div class="inv-head">
      <div class="inv-title">Fakture
        <span x-show="hidePaid">{{ $activeCount }} aktivnih od {{ $invTotal }}</span>
        <span x-show="!hidePaid" x-cloak>{{ $invTotal }}</span>
      </div>
      <button type="button" class="switch-wrap" @click="hidePaid = !hidePaid" :aria-pressed="hidePaid">
        <div>Sakrij plaćene</div>
        <div class="switch" :class="hidePaid && 'on'"></div>
      </button>
    </div>

    @foreach ($groups as $g)
      <div class="group" @if ($g['key'] === 'placeno') x-show="!hidePaid" x-cloak @endif>
        <div class="group-h"><div class="dot bg-{{ $g['key'] }}"></div>{{ $g['title'] }}<span>{{ $g['items']->count() }}</span></div>
        @foreach ($g['items'] as $i)
          @php
              $st = $i->status;
              $pct = $i->amount > 0 ? min(100, $i->paid / $i->amount * 100) : 0;
              $pay = ['url' => route('payments.store', $i->inv), 'sup' => $s->name, 'no' => $i->inv->number, 'amt' => Fmt::rsd($i->amount), 'rem' => Fmt::rsd($i->rem), 'remNum' => Fmt::num($i->rem)];
          @endphp
          <div class="glass inv st-{{ $st }}">
            <div class="inv-row" @click="expanded = expanded === {{ $i->inv->id }} ? null : {{ $i->inv->id }}">
              <div class="date-block av-{{ $st }}">
                <b>{{ $i->due->format('d') }}</b>
                <small>{{ Fmt::month($i->due) }}</small>
              </div>
              <div class="inv-mid">
                <div class="inv-mid-top">
                  <div class="inv-no">Faktura br. {{ $i->inv->number }} <span>izdata {{ Fmt::date($i->inv->issued_on) }}</span></div>
                  <div class="inv-days fg-{{ $st }}">{{ Ledger::daysText($i) }}</div>
                </div>
                <div class="progress"><div style="width:{{ round($pct, 2) }}%"></div></div>
                <div class="inv-paid">Uplaćeno {{ Fmt::rsd($i->paid) }} od {{ Fmt::rsd($i->amount) }}</div>
              </div>
              <div class="inv-right">
                <div class="inv-rem">
                  <small>Saldo</small>
                  <b>{{ Fmt::rsd($i->rem) }}</b>
                </div>
                @if ($i->rem > 0)
                  <button type="button" class="btn-pay" @click.stop="openPayment(@js($pay))">Uplata</button>
                @endif
              </div>
            </div>
            <div class="inv-detail" x-show="expanded === {{ $i->inv->id }}" x-cloak>
              @forelse ($i->inv->payments as $p)
                <div class="pay-row">
                  <div class="dot"></div>
                  <div>Uplata {{ Fmt::date($p->paid_on) }}</div>
                  <div class="spacer"></div>
                  <b>{{ Fmt::rsd(Fmt::cents($p->amount)) }}</b>
                  <form method="POST" action="{{ route('payments.destroy', $p) }}" onsubmit="return confirm('Ukloniti ovu uplatu?')">
                    @csrf @method('DELETE')
                    <button class="link-btn pay-del">ukloni</button>
                  </form>
                </div>
              @empty
                <div class="muted" style="font-size:13.5px;padding:4px 0">Još nema uplata.</div>
              @endforelse
              <div class="inv-detail-foot">
                <div class="muted">Rok {{ Fmt::date($i->due) }} · valuta {{ $i->inv->term_days }} dana</div>
                <form method="POST" action="{{ route('invoices.destroy', $i->inv) }}" onsubmit="return confirm('Obrisati fakturu i njene uplate?')">
                  @csrf @method('DELETE')
                  <button class="link-btn danger-link">Obriši fakturu</button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endforeach

    @if ($invTotal === 0)
      <div class="empty-box">Još nema faktura za ovog dobavljača.</div>
    @elseif ($activeCount === 0)
      <div class="empty-box" x-show="hidePaid">Sve fakture su plaćene.</div>
    @endif
  </div>

  <div class="side-col">
    <div class="glass contacts-card">
      <div class="contacts-head">
        <div class="card-title" style="font-size:15px">Kontakti komercijalista</div>
        <button type="button" class="chip-add" @click="openContact()">+ Dodaj</button>
      </div>
      <div style="padding:0 10px 10px">
        @forelse ($s->contacts as $c)
          <div class="contact">
            <div class="contact-av">{{ Fmt::initials($c->name) }}</div>
            <div style="flex:1;min-width:0">
              <div class="contact-name">{{ $c->name }}</div>
              <div class="contact-role">{{ $c->role }}</div>
            </div>
            @if ($c->phone)
              <a href="tel:{{ preg_replace('/\s+/', '', $c->phone) }}" class="tel">{{ $c->phone }}</a>
            @endif
            <form method="POST" action="{{ route('contacts.destroy', $c) }}" onsubmit="return confirm('Ukloniti kontakt?')">
              @csrf @method('DELETE')
              <button class="link-btn x-btn" aria-label="Ukloni kontakt">×</button>
            </form>
          </div>
        @empty
          <div class="muted" style="padding:12px 8px;font-size:13.5px">Nema unetih kontakata.</div>
        @endforelse
      </div>
    </div>
    <div class="glass facts">
      <div class="fact"><div>PIB</div><div>{{ $s->pib ?: '—' }}</div></div>
      <div class="fact"><div>Valuta plaćanja</div><div>{{ $term }} dana</div></div>
      <div class="fact"><div>Najbliži rok</div><div>{{ $cur->next ? Fmt::date($cur->next->due) : '—' }}</div></div>
    </div>
    <form method="POST" action="{{ route('suppliers.destroy', $s) }}" onsubmit="return confirm(@js('Obrisati dobavljača '.$s->name.' i sve njegove fakture?'))">
      @csrf @method('DELETE')
      <button class="link-btn subtle-link">Obriši dobavljača</button>
    </form>
  </div>
</div>
@endsection

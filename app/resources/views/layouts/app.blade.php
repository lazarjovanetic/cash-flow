@php
    use App\Support\Fmt;
    $W = $ledger->warnDays;
    $t = $ledger->totals();
    $alertCount = $ledger->alerts()->count();
    $curId = isset($cur) && $cur ? $cur->supplier->id : null;
    $isHome = ! $curId;
    $defTerm = (int) config('dugovanja.podrazumevani_rok');
    $supplierOptions = $ledger->suppliers->sortBy(fn ($a) => mb_strtolower($a->supplier->name))->values();
    $openModal = old('_modal');
    $cfg = [
        'today' => $ledger->today->format('Y-m-d'),
        'defTerm' => $defTerm,
        'terms' => $ledger->suppliers->mapWithKeys(fn ($a) => [$a->supplier->id => $a->supplier->term_days])->all(),
        'firstSupplier' => $supplierOptions->first()?->supplier->id,
        'curSupplier' => $curId,
        'routes' => [
            'invoice' => route('invoices.store'),
            'supplier' => route('suppliers.store'),
            'contact' => $curId ? route('contacts.store', $curId) : null,
        ],
        'modal' => $openModal,
        'old' => $openModal ? collect(old())->except(['_token', '_method'])->all() : (object) [],
        'err' => $openModal ? $errors->first() : '',
        'pay' => $openModal === 'payment' ? json_decode((string) old('_pay'), true) : null,
    ];
@endphp
@extends('layouts.base')

@section('body')
<div class="shell" x-data="dugovanja(@js($cfg))" @keydown.escape.window="close()">
  <div class="backdrop"></div>

  <aside class="sidebar">
    <div class="sidebar-card">
      <a href="{{ route('home') }}" class="brand">
        <div class="logo"></div>
        <div>
          <div class="brand-title">Dugovanja</div>
          <div class="brand-sub">Dobavljači marketa</div>
        </div>
      </a>
      <div class="nav-home-wrap">
        <a href="{{ route('home') }}" class="nav-home {{ $isHome ? 'active' : '' }}">
          <div style="flex:1">Pregled</div>
          @if ($alertCount)
            <div class="nav-badge {{ $t['overCount'] ? 'has-over' : '' }}">{{ $alertCount }}</div>
          @endif
        </a>
      </div>
      <div class="side-head">
        <div class="eyebrow">Dobavljači · {{ $ledger->suppliers->count() }}</div>
        <button type="button" class="icon-plus" title="Novi dobavljač" @click="openSupplier()">+</button>
      </div>
      <nav class="side-list">
        @foreach ($supplierOptions as $a)
          <a href="{{ route('suppliers.show', $a->supplier) }}" class="side-item {{ $curId === $a->supplier->id ? 'active' : '' }}">
            <div class="side-avatar av-{{ $a->status }}">{{ Fmt::initials($a->supplier->name) }}</div>
            <div class="ellipsis" style="flex:1;min-width:0">{{ $a->supplier->name }}</div>
            <div class="side-amt {{ in_array($a->status, ['kasni', 'uskoro']) ? 'fg-'.$a->status : '' }}">{{ Fmt::short($a->total) ?: '—' }}</div>
          </a>
        @endforeach
      </nav>
      <div class="side-foot">
        <div class="side-foot-row">
          <div class="fg-kasni"><div class="dot sm bg-kasni"></div>Kasni</div>
          <div>{{ Fmt::rsd($t['over']) }}</div>
        </div>
        <div class="side-foot-row">
          <div class="fg-uskoro"><div class="dot sm bg-uskoro"></div>{{ $W }} dana</div>
          <div>{{ Fmt::rsd($t['soon']) }}</div>
        </div>
        <button type="button" class="btn btn-primary" @click="openInvoice(curSupplier)">+ Nova faktura</button>
      </div>
      <form method="POST" action="{{ route('logout') }}" class="side-logout">
        @csrf
        <button class="link-btn">Odjava · {{ auth()->user()->name }}</button>
      </form>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="head-row">
        <a href="{{ route('home') }}" class="logo sm only-compact" aria-label="Pregled"></a>
        <div class="crumbs">
          <a href="{{ route('home') }}">Pregled</a>
          @if ($curId)<div>/</div><strong>{{ $cur->supplier->name }}</strong>@endif
        </div>
        <div class="spacer"></div>
        <div class="pills">
          @if ($t['over'] > 0)
            <a href="{{ route('home') }}" class="pill-alert kasni"><div class="dot sm glow bg-kasni"></div>Kasni {{ Fmt::short($t['over']) }}</a>
          @endif
          @if ($t['soon'] > 0)
            <a href="{{ route('home') }}" class="pill-alert uskoro"><div class="dot sm glow bg-uskoro"></div>{{ $W }} dana: {{ Fmt::short($t['soon']) }}</a>
          @endif
        </div>
        <button type="button" class="btn btn-ghost not-mobile" @click="openSupplier()">+ Dobavljač</button>
        <button type="button" class="btn btn-primary only-compact" @click="openInvoice(curSupplier)">+ Faktura</button>
      </div>
    </header>

    <main class="content">
      @yield('content')
    </main>
  </div>

  @include('partials.modals', ['supplierOptions' => $supplierOptions, 'curId' => $curId])
</div>
@endsection

@extends('layouts.base')
@section('title', 'Prijava')

@section('body')
<div class="backdrop"></div>
<div class="login-wrap">
  <form method="POST" action="{{ route('login') }}" class="glass login fields">
    @csrf
    <div class="brand">
      <div class="logo"></div>
      <div>
        <div class="brand-title">Dugovanja</div>
        <div class="brand-sub">Dobavljači marketa</div>
      </div>
    </div>
    <label class="field">Email
      <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
    </label>
    <label class="field">Lozinka
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    @if ($errors->any())
      <div class="form-err">{{ $errors->first() }}</div>
    @endif
    <button class="btn btn-primary">Prijavi se</button>
  </form>
</div>
@endsection

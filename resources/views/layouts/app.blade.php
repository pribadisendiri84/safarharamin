<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#013180">
<meta name="description" content="@yield('meta', $site->tagline)">
<title>@yield('title', $site->name) — {{ $site->titleSuffix }}</title>
<link rel="icon" type="image/webp" href="{{ $site->logoUrl }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.default.min.css">
</head>
<body>
<header class="topbar">
  <div class="wrap bar">
    <a class="brand" href="{{ route('home') }}">
      <img class="brand-logo" src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
    </a>
    @php
      $navPackage = request()->route('package');
      $navType = $navPackage instanceof \App\Models\Package ? $navPackage->type : request('tipe');
      $navUmroh = request('kelompok') === 'umroh' || in_array($navType, \App\Models\Package::UMROH_TYPES, true);
      $navHaji = request()->routeIs('haji') || request('kelompok') === 'haji' || in_array($navType, \App\Models\Package::HAJI_TYPES, true);
    @endphp
    <nav class="nav" id="nav">
      <a href="{{ route('packages.index', ['kelompok' => 'umroh']) }}" class="{{ $navUmroh ? 'on' : '' }}">Umroh</a>
      <a href="{{ route('haji') }}" class="{{ $navHaji ? 'on' : '' }}">Haji</a>
      <a href="{{ route('gallery') }}" class="{{ request()->routeIs('gallery') ? 'on' : '' }}">Gallery</a>
      <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'on' : '' }}">Daftar</a>
    </nav>
    <a class="btn ghost" href="{{ route('go.whatsapp', ['from' => 'header']) }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>Chat WhatsApp</a>
    <button class="nav-toggle" type="button" onclick="document.getElementById('nav').classList.toggle('open')" aria-label="Menu"><i class="bi bi-list"></i></button>
  </div>
</header>

<main>
  @yield('content')
</main>

<footer class="foot">
  <div class="wrap foot-grid">
    <div>
      <div class="brand">
        <img class="brand-logo" src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
      </div>
      <p>{{ $site->tagline }}</p>
    </div>
    <div>
      <b>Umroh</b>
      <a href="{{ route('packages.index', ['kelompok' => 'umroh']) }}">Semua umroh</a>
      <a href="{{ route('packages.index', ['tipe' => 'umroh']) }}">Reguler</a>
      <a href="{{ route('packages.index', ['tipe' => 'umroh_plus']) }}">Plus</a>
      <a href="{{ route('packages.index', ['tipe' => 'umroh_ramadhan']) }}">Ramadhan</a>
    </div>
    <div>
      <b>Haji &amp; layanan</b>
      <a href="{{ route('haji') }}">Haji plus &amp; furoda</a>
      <a href="{{ route('testimonials') }}">Testimoni</a>
      <a href="{{ route('register') }}">Daftar sekarang</a>
    </div>
  </div>
  <div class="wrap copy">© {{ date('Y') }} {{ $site->name }}</div>
</footer>

<a class="wa-float" href="{{ route('go.whatsapp', ['from' => 'float']) }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>Tanya via WhatsApp</a>
@include('admin.partials.searchable-select-script')
</body>
</html>

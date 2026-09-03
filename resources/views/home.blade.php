@extends('layouts.app')

@section('title', 'Travel Haji dan Umroh')
@section('content')
<section class="hero">
  <div class="wrap hero-inner">
    <p class="eyebrow">{{ number_format($total, 0, ',', '.') }} paket siap berangkat</p>
    <h1>Perjalanan spiritual Anda dimulai di sini</h1>
    <p class="lead">Pilih paket, cek seat dan harga, lalu daftar via WhatsApp. Mulai dari kota keberangkatan Anda.</p>
    <form class="search" action="{{ route('packages.index') }}" method="get">
      <input type="search" name="q" placeholder="Cari paket, hotel, atau maskapai…">
      @include('partials.city-select', [
        'name' => 'kota',
        'empty' => 'Semua embarkasi',
        'placeholder' => 'Cari embarkasi…',
        'cities' => collect(\App\Models\City::options())->filter(fn ($label, $key) => ($counts[$key] ?? 0) > 0)->all(),
      ])
      <button class="btn" type="submit">Lihat paket</button>
    </form>
    <div class="cities">
      @foreach(\App\Models\City::options() as $key => $label)
        @continue(($counts[$key] ?? 0) === 0)
        <a href="{{ route('packages.index', ['kota' => $key]) }}">{{ $label }} <small>{{ $counts[$key] }}</small></a>
      @endforeach
    </div>
  </div>
</section>

<section class="wrap picks-bar">
  <div class="split-pick">
    <a href="{{ route('packages.index', ['kelompok' => 'umroh']) }}">
      <span class="eyebrow"><i class="bi bi-moon-stars"></i> Umroh</span>
      <b>Reguler, plus, ramadhan</b>
      <p>{{ number_format($umrohCount, 0, ',', '.') }} paket · filter embarkasi dan harga</p>
    </a>
    <a href="{{ route('haji') }}">
      <span class="eyebrow"><i class="bi bi-building"></i> Haji</span>
      <b>Plus dan furoda</b>
      <p>{{ number_format($hajiCount, 0, ',', '.') }} paket · hotel, manasik, kuota musim ini</p>
    </a>
  </div>
</section>

<section class="wrap section">
  <div class="section-head">
    <h2>Paket yang bisa dipilih</h2>
    <a href="{{ route('packages.index') }}">Lihat semua →</a>
  </div>
  <div class="grid">
    @forelse($featured as $package)
      @include('partials.package-card', ['package' => $package])
    @empty
      <p class="empty">Belum ada paket. Tambah dari admin.</p>
    @endforelse
  </div>
</section>

<section class="trust">
  <div class="wrap trust-row">
    <div><b><i class="bi bi-patch-check"></i> Berizin resmi</b><p>Penyelenggara sesuai ketentuan Kemenag.</p></div>
    <div><b><i class="bi bi-people"></i> Pendampingan</b><p>Manasik dan muthawwif berbahasa Indonesia.</p></div>
    <div><b><i class="bi bi-ticket-perforated"></i> Seat terbuka</b><p>Harga dan sisa kuota terlihat di katalog.</p></div>
  </div>
</section>

@if($testimonials->isNotEmpty())
<section class="wrap section">
  <div class="section-head">
    <h2>Kata jamaah</h2>
    <a href="{{ route('testimonials') }}">Lihat testimoni →</a>
  </div>
  <div class="quote-grid">
    @foreach($testimonials as $item)
      <blockquote>
        <p>“{{ $item->quote }}”</p>
        <cite>{{ $item->name }}@if($item->city), {{ $item->city }}@endif</cite>
      </blockquote>
    @endforeach
  </div>
</section>
@endif

@if($gallery->isNotEmpty())
<section class="wrap section">
  <div class="section-head">
    <h2>Gallery keberangkatan</h2>
    <a href="{{ route('gallery') }}">Lihat gallery →</a>
  </div>
  <div class="gallery-home gallery-zoom-grid">
    @foreach($gallery as $item)
      @include('partials.gallery-item', ['item' => $item])
    @endforeach
  </div>
  @include('partials.gallery-lightbox')
</section>
@endif

<section class="cta">
  <div class="wrap cta-box">
    <div>
      <h2>Siap pilih paket?</h2>
      <p>Bandingkan dulu, lalu daftar atau tanya seat via WhatsApp.</p>
    </div>
    <div class="cta-actions">
      <a class="btn" href="{{ route('packages.index') }}">Lihat paket</a>
      <a class="btn light" href="{{ route('register') }}">Daftar sekarang</a>
    </div>
  </div>
</section>
@endsection

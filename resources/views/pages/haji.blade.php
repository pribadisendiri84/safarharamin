@extends('layouts.app')

@section('title', 'Haji Plus')
@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Haji</p>
    <h1>Haji plus &amp; furoda</h1>
    <p>Hotel dekat masjid, pendampingan manasik, dan kepastian jadwal. Kuota terbatas setiap musim.</p>
  </div>
</section>
<section class="wrap why-grid page-benefits">
  <article><b>Hotel ring 1</b><p>Jarak tempuh ke Masjidil Haram dan Nabawi dibuat sesingkat mungkin.</p></article>
  <article><b>Manasik</b><p>Bimbingan sebelum berangkat, bukan dilepas di bandara.</p></article>
  <article><b>Visa &amp; dokumen</b><p>Tim mengurus kelengkapan sesuai musim haji.</p></article>
  <article><b>Perlengkapan</b><p>Koper, ihram, dan kit jamaah mengikuti paket yang dipilih.</p></article>
</section>
<section class="wrap section">
  <div class="section-head">
    <h2>Paket haji tersedia</h2>
    <a href="{{ route('packages.index', ['tipe' => 'haji_plus']) }}">Semua haji →</a>
  </div>
  <div class="grid">
    @forelse($samples as $package)
      @include('partials.package-card', ['package' => $package])
    @empty
      <p class="empty">Paket haji belum dibuka. Tanya via WhatsApp.</p>
    @endforelse
  </div>
</section>
@endsection

@extends('layouts.app')

@section('title', $package->title)
@section('meta', $package->title.' — '.$package->formattedPrice())
@section('content')
<section class="wrap detail">
  <a class="back" href="{{ route('packages.index', ['tipe' => $package->type]) }}">← Kembali ke {{ $package->typeLabel() }}</a>

  <div class="gallery" id="gallery">
    @foreach($package->gallery() as $i => $src)
      <button type="button" class="shot {{ $i === 0 ? 'hero-shot' : '' }}" data-src="{{ $src }}">
        <img src="{{ $src }}" alt="{{ $package->title }} foto {{ $i + 1 }}">
      </button>
    @endforeach
  </div>
  <div class="lightbox" id="lightbox" hidden>
    <button type="button" class="lightbox-close">×</button>
    <img alt="{{ $package->title }}">
  </div>

  <div class="detail-grid">
    <div>
      <div class="badges">
        @if($package->is_hot)<span class="badge hot">Kuota terbatas</span>@endif
        <span class="badge gold">{{ $package->hotel_stars }}★</span>
        <span class="badge">{{ $package->typeLabel() }}</span>
      </div>
      <h1>{{ $package->title }}</h1>
      <p class="loc">{{ $package->departureLine() }} · {{ $package->seatsLine() }}</p>
      <div class="price-row">
        <strong>{{ $package->formattedPrice() }}</strong>
        @if($package->formattedOriginalPrice())
          <s>{{ $package->formattedOriginalPrice() }}</s>
          <span class="badge disc">-{{ $package->discountPercent() }}%</span>
        @endif
      </div>
      <ul class="spec-grid">
        <li><span>Durasi</span><b>{{ $package->duration_days }} hari</b></li>
        <li><span>Kamar</span><b>{{ $package->roomLabel() }}</b></li>
        <li><span>Maskapai</span><b>{{ $package->airline ?: '-' }}</b></li>
        <li><span>Hotel Makkah</span><b>{{ $package->hotel_makkah ?: '-' }}</b></li>
        <li><span>Hotel Madinah</span><b>{{ $package->hotel_madinah ?: '-' }}</b></li>
        <li><span>Bintang</span><b>{{ $package->hotel_stars }}★</b></li>
      </ul>

      <div class="prose">
        <h2>Fasilitas</h2>
        <ul class="checks">
          @foreach($package->facilities ?? [] as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
        <h2>Deskripsi</h2>
        <p>{{ $package->description }}</p>
        @if($package->itinerary)
          <h2>Itinerary</h2>
          <p style="white-space:pre-line">{{ $package->itinerary }}</p>
        @endif
      </div>
    </div>

    <aside class="ask">
      <h3>Tanya / amankan seat</h3>
      <p>Data masuk ke admin. Balasan via WhatsApp.</p>
      @if(session('ok'))
        <div class="alert ok">{{ session('ok') }}</div>
        @if(session('wa_url'))
          <a class="btn full" href="{{ session('wa_url') }}" target="_blank" rel="noopener">Lanjut ke WhatsApp</a>
        @endif
      @endif
      @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
      <form method="post" action="{{ route('packages.inquire', $package) }}" class="ask-form">
        @csrf
        <label>Nama<input name="name" value="{{ old('name') }}" required></label>
        <label>WhatsApp<input name="phone" value="{{ old('phone') }}" required></label>
        <label>Jumlah jamaah<input type="number" name="pax" value="{{ old('pax', 1) }}" min="1" max="20"></label>
        <label>Catatan<textarea name="notes" rows="2">{{ old('notes') }}</textarea></label>
        <button class="btn full" type="submit">Kirim &amp; buka WhatsApp</button>
      </form>
      <a class="btn light full" href="{{ route('register', ['package_id' => $package->id]) }}">Daftar paket ini</a>
    </aside>
  </div>
</section>

@if($related->isNotEmpty())
<section class="wrap section">
  <div class="section-head"><h2>Paket serupa</h2></div>
  <div class="grid">
    @foreach($related as $item)
      @include('partials.package-card', ['package' => $item])
    @endforeach
  </div>
</section>
@endif
<script>
(function () {
  const box = document.getElementById('lightbox');
  const img = box.querySelector('img');
  document.getElementById('gallery').addEventListener('click', function (e) {
    const btn = e.target.closest('[data-src]');
    if (!btn) return;
    img.src = btn.dataset.src;
    box.hidden = false;
  });
  box.addEventListener('click', function () { box.hidden = true; img.src = ''; });
})();
</script>
@endsection

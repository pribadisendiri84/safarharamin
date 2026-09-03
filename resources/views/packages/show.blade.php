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
        @if($package->isFullbook())<span class="badge fullbook">Fullbook</span>@endif
        @if($package->is_hot)<span class="badge hot">Kuota terbatas</span>@endif
        <span class="badge gold">{{ $package->hotel_stars }}★</span>
        <span class="badge">{{ $package->typeLabel() }}</span>
      </div>
      <h1>{{ $package->title }}</h1>
      <p class="loc">{{ $package->departureLine() }} · {{ $package->seatsLine() }}</p>
      <div class="price-row">
        <strong>{{ $package->formattedStartingPrice() }}<small class="price-unit">/jamaah</small></strong>
        @if($package->formattedOriginalPrice())
          <s>{{ $package->formattedOriginalPrice() }}</s>
          <span class="badge disc">-{{ $package->discountPercent() }}%</span>
        @endif
      </div>
      @if($package->price_note)
        <p class="price-note">{{ $package->price_note }}</p>
      @endif
      @if($package->roomPriceList() !== [])
        <h2 class="room-prices-head">Harga per jamaah</h2>
        <p class="room-prices-note">Tergantung isi kamar (jumlah orang per kamar).</p>
        <ul class="room-prices">
          @foreach($package->roomPriceList() as $row)
            <li>
              <span>{{ $row['full_label'] }}</span>
              <b>{{ $package->formattedMoney($row['price']) }}<small>/jamaah</small></b>
            </li>
          @endforeach
        </ul>
      @endif
      <ul class="spec-grid">
        <li><span>Durasi</span><b>{{ $package->duration_days }} hari</b></li>
        <li><span>Maskapai</span><b>{{ $package->airline ?: '-' }}</b></li>
        <li><span>Hotel Makkah</span><b>{{ $package->hotel_makkah ?: '-' }}</b></li>
        <li><span>Hotel Madinah</span><b>{{ $package->hotel_madinah ?: '-' }}</b></li>
        @if($package->isHaji())
          <li><span>Hotel Transit</span><b>{{ $package->hotel_transit ?: '-' }}</b></li>
          <li><span>Maktab</span><b>{{ $package->hotel_maktab ?: '-' }}</b></li>
        @endif
        <li><span>Bintang</span><b>{{ $package->hotel_stars }}★</b></li>
        <li><span>Seat</span><b>{{ $package->seatsLine() }}</b></li>
      </ul>

      <div class="prose">
        @if(($package->facilities ?? []) !== [])
          <h2>Fasilitas</h2>
          <ul class="checks">
            @foreach($package->facilities as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        @endif
        @if(($package->exclusions ?? []) !== [])
          <h2>Tidak termasuk</h2>
          <ul class="checks excludes">
            @foreach($package->exclusions as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ul>
        @endif
        @if($package->description)
          <h2>Deskripsi</h2>
          <p>{{ $package->description }}</p>
        @endif
        @if($package->itinerary)
          <h2>Itinerary</h2>
          <p style="white-space:pre-line">{{ $package->itinerary }}</p>
        @endif
      </div>
    </div>

    <aside class="ask">
      @if($package->isFullbook())
        <h3>Paket fullbook</h3>
        <div class="alert err">Kuota paket ini sudah penuh. Hubungi kami untuk info jadwal berikutnya atau paket serupa.</div>
        <a class="btn light full" href="{{ route('packages.index', ['tipe' => $package->type]) }}">Lihat paket lain</a>
      @else
        <h3>Tanya / amankan seat</h3>
        @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('packages.inquire', $package) }}" class="ask-form">
          @csrf
          <label>Nama<input name="name" value="{{ old('name') }}" required></label>
          <label>WhatsApp<input name="phone" value="{{ old('phone') }}" required></label>
          <label>Jumlah jamaah<input type="number" name="pax" value="{{ old('pax', 1) }}" min="1" max="20"></label>
          <label>Catatan<textarea name="notes" rows="2">{{ old('notes') }}</textarea></label>
          <button class="btn full" type="submit">Tanya via WhatsApp</button>
        </form>
        <a class="btn light full" href="{{ route('register', ['package_id' => $package->id]) }}">Daftar paket ini</a>
      @endif
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

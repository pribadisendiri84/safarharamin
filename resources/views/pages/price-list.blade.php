@extends('layouts.app')

@section('title', 'Daftar Harga Paket')
@section('meta', 'Daftar harga umroh dan haji terbaru per jenis paket dan maskapai — '.$site->name)

@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Price List</p>
    <h1>Daftar harga paket</h1>
    <p>{{ $total }} keberangkatan tayang · harga quad, triple, dan double per periode.</p>
  </div>
</section>

<section class="wrap price-list-layout">
  <form class="filters" method="get" action="{{ route('price-list') }}">
    <label>Cari paket
      <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Judul paket">
    </label>
    <label>Jenis paket
      <select name="tipe">
        <option value="">Semua</option>
        @foreach(\App\Models\Package::TYPES as $key => $label)
          <option value="{{ $key }}" @selected(($filters['tipe'] ?? '') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Tipe paket
      <select name="jenis">
        <option value="">Semua</option>
        @foreach($packageKinds as $kind)
          <option value="{{ $kind->slug }}" @selected(($filters['jenis'] ?? '') === $kind->slug)>{{ $kind->name }}</option>
        @endforeach
      </select>
    </label>
    <label>Maskapai
      @include('partials.airline-select', [
        'name' => 'maskapai',
        'selected' => $filters['maskapai'] ?? '',
        'empty' => 'Semua maskapai',
        'placeholder' => 'Cari maskapai…',
      ])
    </label>
    <label>Berangkat dari
      @include('partials.city-select', [
        'name' => 'kota',
        'selected' => $filters['kota'] ?? '',
        'empty' => 'Semua embarkasi',
        'placeholder' => 'Cari kota…',
      ])
    </label>
    <label>Tanggal dari
      <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}">
    </label>
    <label>Tanggal sampai
      <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}">
    </label>
    <button class="btn" type="submit">Terapkan filter</button>
  </form>

  <div class="price-list-groups">
    @forelse($groups as $typeLabel => $airlineGroups)
      <section class="price-list-group">
        <h2 class="price-list-type">{{ $typeLabel }}</h2>
        @foreach($airlineGroups as $airline => $packages)
          <div class="price-list-airline-block">
            <h3 class="price-list-airline">
              @if($logo = \App\Models\Airline::logoFor($airline !== 'Maskapai menyusul' ? $airline : null))
                <img class="price-list-airline-logo" src="{{ $logo }}" alt="" loading="lazy">
              @endif
              <span>{{ $airline }}</span>
            </h3>
            <div class="price-list-table-wrap">
              <div class="price-list-table">
                <div class="price-list-row price-list-row-head">
                  <span>Paket / Periode</span>
                  <span>Keberangkat</span>
                  <span>Quad</span>
                  <span>Triple</span>
                  <span>Double</span>
                  <span></span>
                </div>
                @foreach($packages as $package)
                  @php
                    $rooms = collect($package->roomPriceList());
                    $quad = $rooms->firstWhere('key', 'quad');
                    $triple = $rooms->firstWhere('key', 'triple');
                    $double = $rooms->firstWhere('key', 'double');
                    $money = fn (?int $amount) => $amount && $amount > 0 ? $package->formattedMoney($amount) : '—';
                  @endphp
                  <div class="price-list-row">
                    <span>
                      <b>{{ $package->title }}</b>
                      @if($package->packageKindLabel() !== '')
                        <small>{{ $package->packageKindLabel() }} · {{ $package->duration_days }} hari</small>
                      @else
                        <small>{{ $package->duration_days }} hari</small>
                      @endif
                    </span>
                    <span>{{ $package->catalogDepartureDateLine() ?? 'Menyusul' }}<small>{{ $package->routeLine() }}</small></span>
                    <span>{{ $money($quad ? ($quad['price'] ?? null) : (int) $package->price) }}</span>
                    <span>{{ $money($triple ? ($triple['price'] ?? null) : null) }}</span>
                    <span>{{ $money($double ? ($double['price'] ?? null) : null) }}</span>
                    <span><a class="btn ghost" href="{{ route('packages.show', $package) }}">Detail</a></span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </section>
    @empty
      <div class="empty">Belum ada paket tayang untuk filter ini.</div>
    @endforelse
  </div>
</section>

<section class="cta">
  <div class="wrap">
    <div class="cta-box">
      <div>
        <h2>Butuh rekomendasi paket?</h2>
        <p>Tim kami bantu pilih jadwal dan tipe kamar sesuai budget.</p>
      </div>
      <div class="cta-actions">
        <a class="btn" href="{{ route('go.whatsapp', ['from' => 'price-list']) }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> Chat WhatsApp</a>
        <a class="btn ghost" href="{{ route('packages.index') }}">Lihat katalog</a>
      </div>
    </div>
  </div>
</section>
@endsection

@extends('layouts.app')

@section('title', $typeLabel)
@section('content')
<section class="page-head">
  <div class="wrap">
    <p class="eyebrow">Katalog</p>
    <h1>{{ $typeLabel }}</h1>
    <p>{{ $packages->total() }} keberangkatan yang bisa Anda pilih.</p>
    <div class="city-chips">
      <a href="{{ route('packages.index', array_filter([...$filters, 'kota' => null])) }}" class="{{ empty($filters['kota']) ? 'on' : '' }}">Semua embarkasi</a>
      @foreach(\App\Models\City::options($filters['kota'] ?? null) as $key => $label)
        @continue(! $chipCities->contains($key))
        <a href="{{ route('packages.index', array_filter([...$filters, 'kota' => $key])) }}" class="{{ ($filters['kota'] ?? '') === $key ? 'on' : '' }}">{{ $label }}</a>
      @endforeach
    </div>
  </div>
</section>

<section class="wrap listing-layout">
  <form class="filters" method="get" action="{{ route('packages.index') }}">
    @if(! empty($filters['kelompok']))
      <input type="hidden" name="kelompok" value="{{ $filters['kelompok'] }}">
    @endif
    <label>Cari
      <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama paket atau hotel">
    </label>
    <label>Jenis
      <select name="tipe">
        <option value="">Semua</option>
        @foreach(\App\Models\Package::TYPES as $key => $label)
          <option value="{{ $key }}" @selected(($filters['tipe'] ?? '') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Berangkat dari
      @include('partials.city-select', [
        'name' => 'kota',
        'selected' => $filters['kota'] ?? '',
        'empty' => 'Semua embarkasi',
        'placeholder' => 'Cari kota…',
      ])
    </label>
    <label>Harga max
      <input type="number" name="harga_max" value="{{ $filters['harga_max'] ?? '' }}" min="0" step="1000000">
    </label>
    <label>Durasi max (hari)
      <input type="number" name="hari" value="{{ $filters['hari'] ?? '' }}" min="7">
    </label>
    <label>Urutkan
      <select name="urut">
        <option value="">Rekomendasi</option>
        <option value="termurah" @selected(($filters['urut'] ?? '') === 'termurah')>Termurah</option>
        <option value="termahal" @selected(($filters['urut'] ?? '') === 'termahal')>Termahal</option>
        <option value="terdekat" @selected(($filters['urut'] ?? '') === 'terdekat')>Keberangkatan terdekat</option>
      </select>
    </label>
    <button class="btn" type="submit">Terapkan filter</button>
  </form>

  <div>
    <div class="grid">
      @forelse($packages as $package)
        @include('partials.package-card', ['package' => $package])
      @empty
        <p class="empty">Tidak ada paket dengan filter ini.</p>
      @endforelse
    </div>
    @if($packages->hasPages())
      <div class="pager">
        @if($packages->onFirstPage())
          <span class="btn light disabled">Sebelumnya</span>
        @else
          <a class="btn light" href="{{ $packages->previousPageUrl() }}">Sebelumnya</a>
        @endif
        <span>Halaman {{ $packages->currentPage() }} / {{ $packages->lastPage() }}</span>
        @if($packages->hasMorePages())
          <a class="btn light" href="{{ $packages->nextPageUrl() }}">Berikutnya</a>
        @else
          <span class="btn light disabled">Berikutnya</span>
        @endif
      </div>
    @endif
  </div>
</section>
@endsection

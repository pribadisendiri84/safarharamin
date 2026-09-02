@extends('layouts.admin')

@section('title', !empty($isDuplicate) ? 'Duplikat paket' : ($package->exists ? 'Edit paket' : 'Tambah paket'))
@section('content')
<div class="page-head">
  <div>
    <h1>{{ !empty($isDuplicate) ? 'Duplikat paket' : ($package->exists ? 'Edit paket' : 'Tambah paket') }}</h1>
    <p class="sub">{{ !empty($isDuplicate) ? 'Data disalin dari paket lain. Ubah lalu Simpan — belum masuk katalog sebelum disimpan.' : 'Unggah flyer dari komputer. Flyer jadi cover di website.' }}</p>
  </div>
  <div class="actions head-actions">
    <a class="btn ghost" href="{{ route('admin.packages.index') }}">Kembali</a>
  </div>
</div>
<form class="form panel form-pad" method="post" enctype="multipart/form-data" action="{{ $package->exists ? route('admin.packages.update', $package) : route('admin.packages.store') }}">
  @csrf
  @if($package->exists) @method('PUT') @endif
  <label>Unggah flyer
    <input type="file" id="flyer-upload" name="photos[]" accept="image/*" multiple data-upload-preview="flyer-upload">
  </label>
  <p class="sub">Foto otomatis dikecilkan saat disimpan (maks. 1200×1700 px).</p>
  <div class="flyer-strip">
    @if(($package->images ?? []) !== [])
      <div class="flyer-block">
        <p class="flyer-label">Flyer sekarang <span class="flyer-hint">· klik untuk zoom</span></p>
        <div class="flyer-previews">
          @foreach($package->images as $src)
            <button type="button" class="flyer-zoom" data-src="{{ $src }}">
              <img class="thumb flyer" src="{{ $src }}" alt="Flyer {{ $package->title }}">
            </button>
          @endforeach
        </div>
      </div>
    @endif
    <div class="flyer-block" id="flyer-upload-preview-col" hidden>
      <p class="flyer-label">Flyer baru <span class="flyer-hint">· klik untuk zoom</span></p>
      <div class="flyer-previews" id="flyer-upload-preview-grid"></div>
    </div>
  </div>
  @include('admin.partials.flyer-zoom')
  @include('admin.partials.image-upload-preview', ['inputId' => 'flyer-upload', 'embedded' => true])
  <label>Judul paket<input name="title" value="{{ old('title', $package->title) }}" required></label>
  <div class="row2">
    <label>Jenis
      <select name="type" required>
        @foreach(\App\Models\Package::TYPES as $key => $label)
          <option value="{{ $key }}" @selected(old('type', $package->type) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Status
      <select name="status" required>
        @foreach(\App\Models\Package::STATUSES as $key => $label)
          <option value="{{ $key }}" @selected(old('status', $package->status ?? (!empty($isDuplicate) ? 'draft' : 'published')) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
  </div>
  <div class="row2">
    <label>Embarkasi
      @include('partials.city-select', [
        'name' => 'departure_city',
        'selected' => $package->departure_city,
        'empty' => false,
        'required' => true,
        'placeholder' => 'Cari kota embarkasi…',
      ])
    </label>
    <label>Tanggal berangkat<input type="date" name="departure_date" value="{{ old('departure_date', optional($package->departure_date)->format('Y-m-d')) }}"></label>
  </div>
  <div class="row3">
    <label>Quad — 4 org/kamar (Rp)<input type="number" name="price_quad" value="{{ old('price_quad', $package->price_quad ?? $package->price) }}" min="1" required></label>
    <label>Triple — 3 org/kamar (Rp)<input type="number" name="price_triple" value="{{ old('price_triple', $package->price_triple) }}" min="1" required></label>
    <label>Double — 2 org/kamar (Rp)<input type="number" name="price_double" value="{{ old('price_double', $package->price_double) }}" min="1" required></label>
  </div>
  <p class="sub">Harga per jamaah. Semakin sedikit isi kamar, semakin mahal (Quad termurah).</p>
  <div class="row2">
    <label>Harga coret<input type="number" name="original_price" value="{{ old('original_price', $package->original_price) }}" min="1"></label>
    <label>Catatan harga<input name="price_note" value="{{ old('price_note', $package->price_note) }}" maxlength="180" placeholder="Harga dapat berubah sesuai kebijakan"></label>
  </div>
  <div class="row3">
    <label>Durasi (hari)<input type="number" name="duration_days" value="{{ old('duration_days', $package->duration_days ?? 9) }}" min="7" required></label>
    <label>Bintang hotel<input type="number" name="hotel_stars" value="{{ old('hotel_stars', $package->hotel_stars ?? 4) }}" min="3" max="5" required></label>
    <label>Maskapai<input name="airline" value="{{ old('airline', $package->airline) }}"></label>
  </div>
  <div class="row2">
    <label>Hotel Makkah<input name="hotel_makkah" value="{{ old('hotel_makkah', $package->hotel_makkah) }}"></label>
    <label>Hotel Madinah<input name="hotel_madinah" value="{{ old('hotel_madinah', $package->hotel_madinah) }}"></label>
  </div>
  <div class="row2">
    <label>Seat total<input type="number" name="seats_total" value="{{ old('seats_total', $package->seats_total ?? 40) }}" min="1" required></label>
    <label>Seat sisa<input type="number" name="seats_left" value="{{ old('seats_left', $package->seats_left ?? 40) }}" min="0" required></label>
  </div>
  <label>Fasilitas / include (opsional, satu baris satu item)<textarea name="facilities_text" rows="4">{{ old('facilities_text', implode("\n", $package->facilities ?? [])) }}</textarea></label>
  <label>Tidak termasuk (satu baris satu item)<textarea name="exclusions_text" rows="4">{{ old('exclusions_text', implode("\n", $package->exclusions ?? [])) }}</textarea></label>
  <label>Deskripsi (opsional)<textarea name="description" rows="3">{{ old('description', $package->description) }}</textarea></label>
  <label>Itinerary (opsional)<textarea name="itinerary" rows="4">{{ old('itinerary', $package->itinerary) }}</textarea></label>
  <div class="check-row">
    <label class="check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured))> Unggulan</label>
    <label class="check"><input type="checkbox" name="is_hot" value="1" @checked(old('is_hot', $package->is_hot))> Kuota terbatas</label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>
@endsection

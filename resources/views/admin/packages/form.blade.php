@extends('layouts.admin')

@section('title', !empty($isDuplicate) ? 'Duplikat paket' : ($package->exists ? 'Edit paket' : 'Tambah paket'))
@section('content')
@php
  $isHajiPackage = in_array(old('type', $package->type), \App\Models\Package::HAJI_TYPES, true);
@endphp
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
  <p class="sub">Foto otomatis dikecilkan di browser sebelum unggah, lalu dioptimalkan lagi di server (maks. 1200×1700 px).</p>
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
      <select name="type" id="package-type" required>
        @foreach(\App\Models\Package::TYPES as $key => $label)
          <option value="{{ $key }}" @selected(old('type', $package->type) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Tipe paket
      @include('partials.package-kind-select', [
        'selected' => old('package_kind_id', $package->package_kind_id),
      ])
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
    <label>Quad — 4 org/kamar (Rp)<input type="text" class="js-rupiah" name="price_quad" value="{{ old('price_quad', $package->price_quad ?? $package->price) }}"></label>
    <label>Triple — 3 org/kamar (Rp)<input type="text" class="js-rupiah" name="price_triple" value="{{ old('price_triple', $package->price_triple) }}"></label>
    <label>Double — 2 org/kamar (Rp)<input type="text" class="js-rupiah" name="price_double" value="{{ old('price_double', $package->price_double) }}"></label>
  </div>
  <label id="package-double-plus-wrap" @unless($isHajiPackage) hidden @endunless>Double Plus — 2 org/kamar (Rp)<input type="text" class="js-rupiah" name="price_double_plus" value="{{ old('price_double_plus', $package->price_double_plus) }}"></label>
  <p class="sub">Harga per jamaah. Opsional — isi saja tipe kamar yang tersedia di paket ini. Double Plus khusus paket haji.</p>
  <div class="row2">
    <label>Harga coret<input type="text" class="js-rupiah" name="original_price" value="{{ old('original_price', $package->original_price) }}"></label>
    <label>Catatan harga<input name="price_note" value="{{ old('price_note', $package->price_note) }}" maxlength="180" placeholder="Harga dapat berubah sesuai kebijakan"></label>
  </div>
  <div class="row2">
    <label>Durasi (hari)<input type="number" name="duration_days" value="{{ old('duration_days', $package->duration_days ?? 9) }}" min="7" required></label>
    <label>Maskapai
      @include('partials.airline-select', [
        'selected' => old('airline', $package->airline),
      ])
    </label>
  </div>
  <div class="row2">
    <div>
      <label>Hotel Makkah
        @include('partials.hotel-select', [
          'name' => 'hotel_makkah',
          'location' => \App\Models\Hotel::LOCATION_MAKKAH,
          'selected' => old('hotel_makkah', $package->hotel_makkah ?? ''),
        ])
      </label>
      <label class="check">
        <input type="checkbox" name="hotel_makkah_setaraf" value="1" @checked(old('hotel_makkah_setaraf', $package->hotel_makkah_setaraf))>
        Hotel dapat diganti dengan yang setaraf
      </label>
    </div>
    <div>
      <label>Hotel Madinah
        @include('partials.hotel-select', [
          'name' => 'hotel_madinah',
          'location' => \App\Models\Hotel::LOCATION_MADINAH,
          'selected' => old('hotel_madinah', $package->hotel_madinah ?? ''),
        ])
      </label>
      <label class="check">
        <input type="checkbox" name="hotel_madinah_setaraf" value="1" @checked(old('hotel_madinah_setaraf', $package->hotel_madinah_setaraf))>
        Hotel dapat diganti dengan yang setaraf
      </label>
    </div>
  </div>
  <p class="sub">Setaraf = hotel pengganti dengan kelas bintang, fasilitas, dan jarak ke masjid yang sebanding jika hotel utama penuh atau tidak tersedia saat konfirmasi. Nama hotel boleh sama di Makkah dan Madinah.</p>
  <div class="row2 haji-extra-hotels" @unless($isHajiPackage) hidden @endunless>
    <label>Hotel Transit
      @include('partials.hotel-select', [
        'name' => 'hotel_transit',
        'location' => \App\Models\Hotel::LOCATION_TRANSIT,
        'selected' => old('hotel_transit', $package->hotel_transit ?? ''),
      ])
    </label>
    <label>Maktab
      @include('partials.hotel-select', [
        'name' => 'hotel_maktab',
        'location' => \App\Models\Hotel::LOCATION_MAKTAB,
        'selected' => old('hotel_maktab', $package->hotel_maktab ?? ''),
      ])
    </label>
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
    <label class="check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured))> Tampil di beranda</label>
    <label class="check"><input type="checkbox" name="is_hot" value="1" @checked(old('is_hot', $package->is_hot))> Kuota terbatas</label>
  </div>
  <p class="sub">Centang beranda di daftar paket (maks. {{ \App\Models\Package::homeLimit() }}). Urutan lewat drag-drop.</p>
  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var typeSelect = document.getElementById('package-type');
  var doublePlusWrap = document.getElementById('package-double-plus-wrap');
  var hajiExtraHotels = document.querySelector('.haji-extra-hotels');
  if (!typeSelect) return;

  var hajiTypes = @json(\App\Models\Package::HAJI_TYPES);

  function syncPackageKind() {
    var isHaji = hajiTypes.includes(typeSelect.value);
    if (doublePlusWrap) doublePlusWrap.hidden = !isHaji;
    if (hajiExtraHotels) hajiExtraHotels.hidden = !isHaji;
  }

  typeSelect.addEventListener('change', syncPackageKind);
  syncPackageKind();
})();
</script>
@endpush

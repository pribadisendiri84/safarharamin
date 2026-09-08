@extends('layouts.admin')

@section('title', $departure->exists ? 'Edit keberangkatan' : 'Tambah keberangkatan')
@section('content')
@php
  $isHaji = old('program_kind', $departure->program_kind) === 'haji';
@endphp
<div class="page-head">
  <div>
    <h1>{{ $departure->exists ? 'Edit keberangkatan' : 'Tambah keberangkatan' }}</h1>
    <p class="sub">@if(!empty($fromHajiPage))
      Data operasional Haji Plus dari halaman khusus. Field bisa disesuaikan — setelah simpan, snapshot program tersimpan (tidak ikut berubah jika halaman/katalog diubah).
    @else
      Data operasional keberangkatan jamaah. Pilih paket katalog umroh untuk mengisi default — tetap bisa diedit sebelum simpan.
    @endif</p>
  </div>
  @if(! $departure->exists && empty($fromHajiPage))
    <div class="head-actions">
      <a class="btn ghost" href="{{ route('admin.operations.departures.create', ['from' => 'haji_page']) }}">Buat dari Halaman Haji</a>
    </div>
  @endif
</div>

<form class="form panel form-pad" method="post" enctype="multipart/form-data" action="{{ $departure->exists ? route('admin.operations.departures.update', $departure) : route('admin.operations.departures.store') }}">
  @csrf
  @if($departure->exists) @method('PUT') @endif
  <input type="hidden" name="source" value="{{ old('source', $departure->source ?? ($fromHajiPage ?? false ? \App\Models\Departure::SOURCE_HAJI_PAGE : \App\Models\Departure::SOURCE_MANUAL)) }}">

  @unless(!empty($fromHajiPage) || old('program_kind', $departure->program_kind) === 'haji')
  <label>Paket katalog umroh (opsional)
    <select name="package_id" id="departure-package">
      <option value="">— Manual —</option>
      @foreach($packages as $package)
        <option value="{{ $package->id }}" @selected(old('package_id', $departure->package_id) == $package->id)>{{ $package->title }}</option>
      @endforeach
    </select>
  </label>
  <p class="sub">Ganti paket katalog untuk mengisi ulang field dari katalog. Field bisa disesuaikan sebelum simpan.</p>
  @endunless

  @if($departure->exists && !empty($departure->program_snapshot['captured_at']))
    <p class="sub">Snapshot program tersimpan {{ \Carbon\Carbon::parse($departure->program_snapshot['captured_at'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB.</p>
  @endif

  <label>Nama program<input name="program_name" id="departure-program-name" value="{{ old('program_name', $departure->program_name) }}" required></label>

  <div class="row2">
    <label>Jenis
      <select name="program_kind" id="departure-kind" required @if(!empty($fromHajiPage)) disabled @endif>
        @foreach(\App\Models\Departure::KINDS as $key => $label)
          <option value="{{ $key }}" @selected(old('program_kind', $departure->program_kind) === $key)>{{ $label }}</option>
        @endforeach
      </select>
      @if(!empty($fromHajiPage))
        <input type="hidden" name="program_kind" value="haji">
      @endif
    </label>
    <label>Tanggal keberangkatan<input type="date" name="departure_date" id="departure-date" value="{{ old('departure_date', optional($departure->departure_date)->format('Y-m-d')) }}"></label>
  </div>

  <fieldset class="haji-itinerary-fields itinerary-pdf-fieldset" @unless($isHaji) hidden @endunless>
    <legend>Itinerary (halaman Haji Plus)</legend>
    <p class="sub">Tampil di <a href="{{ route('haji') }}" target="_blank" rel="noopener">/haji-khusus</a> sebagai daftar keberangkatan + PDF.</p>
    <div class="row2">
      <label>Tanggal Hijriah
        <input type="text" name="hijri_label" id="departure-hijri-label" value="{{ old('hijri_label', $departure->hijri_label) }}" placeholder="1448 H">
      </label>
      <label>PDF itinerary
        <input type="file" name="itinerary_pdf" accept="application/pdf,.pdf">
      </label>
    </div>
    @if($departure->itinerary_pdf_path)
      <div class="itinerary-existing-item">
        <span class="itinerary-existing-label">PDF saat ini</span>
        <a class="btn gray compact" href="{{ $departure->itinerary_pdf_path }}" target="_blank" rel="noopener">Lihat PDF</a>
        <label class="check">
          <input type="checkbox" name="remove_itinerary_pdf" value="1" @checked(old('remove_itinerary_pdf'))>
          Hapus PDF
        </label>
      </div>
    @endif
  </fieldset>

  <div class="row2">
    <label>Maskapai
      @include('partials.airline-select', [
        'inputId' => 'departure-airline',
        'selected' => old('airline', $departure->airline),
      ])
    </label>
    <label>Nomor penerbangan<input name="flight_number" id="departure-flight-number" value="{{ old('flight_number', $departure->flight_number) }}"></label>
  </div>

  <fieldset class="hotel-fields">
    <legend id="hotel-fields-legend">{{ $isHaji ? 'Hotel haji' : 'Hotel' }}</legend>
    <div class="row2">
      <label>Hotel Makkah
        @include('partials.hotel-select', [
          'name' => 'hotel_makkah',
          'inputId' => 'departure-hotel-makkah',
          'location' => \App\Models\Hotel::LOCATION_MAKKAH,
          'selected' => old('hotel_makkah', $departure->hotel_makkah ?? ''),
        ])
      </label>
      <label>Hotel Madinah
        @include('partials.hotel-select', [
          'name' => 'hotel_madinah',
          'inputId' => 'departure-hotel-madinah',
          'location' => \App\Models\Hotel::LOCATION_MADINAH,
          'selected' => old('hotel_madinah', $departure->hotel_madinah ?? ''),
        ])
      </label>
    </div>
    <div class="row2 haji-extra-hotels" @unless($isHaji) hidden @endunless>
      <label>Hotel Transit
        @include('partials.hotel-select', [
          'name' => 'hotel_transit',
          'inputId' => 'departure-hotel-transit',
          'location' => \App\Models\Hotel::LOCATION_TRANSIT,
          'selected' => old('hotel_transit', $departure->hotel_transit ?? ''),
        ])
      </label>
      <label>Maktab
        @include('partials.hotel-select', [
          'name' => 'hotel_maktab',
          'inputId' => 'departure-hotel-maktab',
          'location' => \App\Models\Hotel::LOCATION_MAKTAB,
          'selected' => old('hotel_maktab', $departure->hotel_maktab ?? ''),
        ])
      </label>
    </div>
  </fieldset>

  <label>Catatan<textarea name="notes" rows="3">{{ old('notes', $departure->notes) }}</textarea></label>

  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
    <a class="btn gray" href="{{ route('admin.operations.departures.index') }}">Batal</a>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var kindSelect = document.getElementById('departure-kind');
  var packageSelect = document.getElementById('departure-package');
  var hajiExtraHotels = document.querySelector('.haji-extra-hotels');
  var hajiItineraryFields = document.querySelector('.haji-itinerary-fields');
  var legend = document.getElementById('hotel-fields-legend');
  var packageCatalog = @json($packageCatalog);

  function setValue(id, value) {
    var node = document.getElementById(id);
    if (!node) return;
    var next = value || '';
    if (node.tomselect) {
      node.tomselect.setValue(next, true);
      return;
    }
    node.value = next;
  }

  function syncKind() {
    var isHaji = kindSelect && kindSelect.value === 'haji';
    if (hajiExtraHotels) hajiExtraHotels.hidden = !isHaji;
    if (hajiItineraryFields) hajiItineraryFields.hidden = !isHaji;
    if (legend) legend.textContent = isHaji ? 'Hotel haji' : 'Hotel';
  }

  function applyPackageDefaults() {
    if (!packageSelect) return;

    var data = packageCatalog[packageSelect.value];
    if (!data) return;

    setValue('departure-program-name', data.program_name);
    setValue('departure-kind', data.program_kind);
    setValue('departure-date', data.departure_date);
    setValue('departure-airline', data.airline);
    setValue('departure-hotel-makkah', data.hotel_makkah);
    setValue('departure-hotel-madinah', data.hotel_madinah);
    setValue('departure-hotel-transit', data.hotel_transit);
    setValue('departure-hotel-maktab', data.hotel_maktab);
    syncKind();
  }

  if (kindSelect) {
    kindSelect.addEventListener('change', syncKind);
    syncKind();
  }

  if (packageSelect) {
    packageSelect.addEventListener('change', applyPackageDefaults);
  }
})();
</script>
@endpush

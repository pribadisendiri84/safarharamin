@extends('layouts.admin')

@section('title', $departure->exists ? 'Edit keberangkatan' : 'Tambah keberangkatan')
@section('content')
@php
  $isHaji = old('program_kind', $departure->program_kind) === 'haji';
@endphp
<div class="page-head">
  <div>
    <h1>{{ $departure->exists ? 'Edit keberangkatan' : 'Tambah keberangkatan' }}</h1>
    <p class="sub">Data operasional keberangkatan jamaah. Pilih paket katalog untuk mengisi default — tetap bisa diedit sebelum simpan.</p>
  </div>
</div>

<form class="form panel form-pad" method="post" action="{{ $departure->exists ? route('admin.operations.departures.update', $departure) : route('admin.operations.departures.store') }}">
  @csrf
  @if($departure->exists) @method('PUT') @endif

  <label>Paket katalog (opsional)
    <select name="package_id" id="departure-package">
      <option value="">— Manual —</option>
      @foreach($packages as $package)
        <option value="{{ $package->id }}" @selected(old('package_id', $departure->package_id) == $package->id)>{{ $package->title }}</option>
      @endforeach
    </select>
  </label>
  <p class="sub">Ganti paket katalog untuk mengisi ulang field dari katalog. Field bisa disesuaikan sebelum simpan.</p>

  <label>Nama program<input name="program_name" id="departure-program-name" value="{{ old('program_name', $departure->program_name) }}" required></label>

  <div class="row2">
    <label>Jenis
      <select name="program_kind" id="departure-kind" required>
        @foreach(\App\Models\Departure::KINDS as $key => $label)
          <option value="{{ $key }}" @selected(old('program_kind', $departure->program_kind) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Tanggal keberangkatan<input type="date" name="departure_date" id="departure-date" value="{{ old('departure_date', optional($departure->departure_date)->format('Y-m-d')) }}"></label>
  </div>

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

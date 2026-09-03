@extends('layouts.admin')

@section('title', $departure->exists ? 'Edit keberangkatan' : 'Tambah keberangkatan')
@section('content')
@php
  $isHaji = old('program_kind', $departure->program_kind) === 'haji';
@endphp
<div class="page-head">
  <div>
    <h1>{{ $departure->exists ? 'Edit keberangkatan' : 'Tambah keberangkatan' }}</h1>
    <p class="sub">Data operasional keberangkatan jamaah.</p>
  </div>
</div>

<form class="form panel form-pad" method="post" action="{{ $departure->exists ? route('admin.operations.departures.update', $departure) : route('admin.operations.departures.store') }}">
  @csrf
  @if($departure->exists) @method('PUT') @endif

  <label>Paket katalog (opsional)
    <select name="package_id">
      <option value="">— Manual —</option>
      @foreach($packages as $package)
        <option value="{{ $package->id }}" @selected(old('package_id', $departure->package_id) == $package->id)>{{ $package->title }}</option>
      @endforeach
    </select>
  </label>

  <label>Nama program<input name="program_name" value="{{ old('program_name', $departure->program_name) }}" required></label>

  <div class="row2">
    <label>Jenis
      <select name="program_kind" id="departure-kind" required>
        @foreach(\App\Models\Departure::KINDS as $key => $label)
          <option value="{{ $key }}" @selected(old('program_kind', $departure->program_kind) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Tanggal keberangkatan<input type="date" name="departure_date" value="{{ old('departure_date', optional($departure->departure_date)->format('Y-m-d')) }}"></label>
  </div>

  <div class="row2">
    <label>Maskapai<input name="airline" value="{{ old('airline', $departure->airline) }}"></label>
    <label>Nomor penerbangan<input name="flight_number" value="{{ old('flight_number', $departure->flight_number) }}"></label>
  </div>

  <fieldset class="hotel-fields">
    <legend id="hotel-fields-legend">{{ $isHaji ? 'Hotel haji' : 'Hotel' }}</legend>
    <div class="row2">
      <label>Hotel Makkah<input name="hotel_makkah" value="{{ old('hotel_makkah', $departure->hotel_makkah ?? '') }}"></label>
      <label>Hotel Madinah<input name="hotel_madinah" value="{{ old('hotel_madinah', $departure->hotel_madinah ?? '') }}"></label>
    </div>
    <div class="row2 haji-extra-hotels" @unless($isHaji) hidden @endunless>
      <label>Hotel Transit<input name="hotel_transit" value="{{ old('hotel_transit', $departure->hotel_transit ?? '') }}"></label>
      <label>Maktab<input name="hotel_maktab" value="{{ old('hotel_maktab', $departure->hotel_maktab ?? '') }}"></label>
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
  var hajiExtraHotels = document.querySelector('.haji-extra-hotels');
  var legend = document.getElementById('hotel-fields-legend');
  if (!kindSelect) return;

  function syncKind() {
    var isHaji = kindSelect.value === 'haji';
    if (hajiExtraHotels) hajiExtraHotels.hidden = !isHaji;
    if (legend) legend.textContent = isHaji ? 'Hotel haji' : 'Hotel';
  }

  kindSelect.addEventListener('change', syncKind);
  syncKind();
})();
</script>
@endpush

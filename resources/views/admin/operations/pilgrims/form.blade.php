@extends('layouts.admin')

@section('title', $pilgrim->exists ? 'Edit jamaah' : 'Tambah jamaah')
@section('content')
@php
  $selectedDeparture = $departures->firstWhere('id', (int) old('departure_id', $pilgrim->departure_id));
  $isHaji = $selectedDeparture?->isHaji() ?? ($pilgrim->departure?->isHaji() ?? false);
  $allRoomTypes = \App\Enums\RoomType::labels();
  $departureForHotels = $pilgrim->exists ? $pilgrim->departure : null;
@endphp
<div class="page-head">
  <div>
    <h1>{{ $pilgrim->exists ? 'Edit jamaah' : 'Tambah jamaah' }}</h1>
    <p class="sub">Input data jamaah dan preferensi tipe kamar.</p>
  </div>
</div>

<form class="form panel form-pad" method="post" action="{{ $pilgrim->exists ? route('admin.operations.pilgrims.update', $pilgrim) : route('admin.operations.pilgrims.store') }}">
  @csrf
  @if($pilgrim->exists) @method('PUT') @endif

  <label>Keberangkatan
    <select name="departure_id" id="pilgrim-departure" required>
      <option value="">Pilih keberangkatan</option>
      @foreach($departures as $departure)
        <option value="{{ $departure->id }}" data-kind="{{ $departure->program_kind }}" @selected(old('departure_id', $pilgrim->departure_id) == $departure->id)>{{ $departure->program_name }} · {{ $departure->kindLabel() }}</option>
      @endforeach
    </select>
  </label>

  <label>Nama lengkap<input name="full_name" value="{{ old('full_name', $pilgrim->full_name) }}" required></label>

  <div class="row2">
    <label>Nomor HP<input name="phone" value="{{ old('phone', $pilgrim->phone) }}"></label>
    <label>Jenis kelamin
      <select name="gender">
        <option value="">—</option>
        @foreach(\App\Models\Pilgrim::GENDERS as $key => $label)
          <option value="{{ $key }}" @selected(old('gender', $pilgrim->gender) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
  </div>

  <div class="row2">
    <label>Tipe kamar
      <select name="room_type" id="pilgrim-room-type" required>
        @foreach($allRoomTypes as $key => $label)
          <option value="{{ $key }}" data-haji-only="{{ $key === 'double_plus' ? '1' : '0' }}" @selected(old('room_type', $pilgrim->room_type) === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Harga paket (Rp)<input type="text" class="js-rupiah" name="package_price" value="{{ old('package_price', $pilgrim->package_price) }}" placeholder="0"></label>
  </div>

  <fieldset class="haji-fields" @unless($isHaji) hidden @endunless>
    <legend>Data khusus haji</legend>
    <div class="row2">
      <label>ID / catatan haji<input name="haji_registration_id" value="{{ old('haji_registration_id', $pilgrim->haji_registration_id) }}"></label>
      <label>Nomor porsi<input name="haji_portion_number" value="{{ old('haji_portion_number', $pilgrim->haji_portion_number) }}"></label>
    </div>
    <p class="sub">Transit &amp; Maktab disimpan per keberangkatan — berlaku untuk semua jamaah di program yang sama.</p>
    <div class="row2">
      <label>Hotel Makkah<input name="hotel_makkah" value="{{ old('hotel_makkah', $departureForHotels?->hotel_makkah ?? '') }}"></label>
      <label>Hotel Madinah<input name="hotel_madinah" value="{{ old('hotel_madinah', $departureForHotels?->hotel_madinah ?? '') }}"></label>
    </div>
    <div class="row2">
      <label>Hotel Transit<input name="hotel_transit" value="{{ old('hotel_transit', $departureForHotels?->hotel_transit ?? '') }}"></label>
      <label>Maktab<input name="hotel_maktab" value="{{ old('hotel_maktab', $departureForHotels?->hotel_maktab ?? '') }}"></label>
    </div>
  </fieldset>

  <label>Catatan<textarea name="notes" rows="3">{{ old('notes', $pilgrim->notes) }}</textarea></label>

  @if($pilgrim->exists && $pilgrim->room_id)
    <div class="alert warn">Jamaah sudah di room <strong>{{ $pilgrim->room?->room_number }}</strong>. Keluarkan dari grouping jika perlu ubah keberangkatan/tipe kamar.</div>
  @endif

  <div class="form-actions">
    <button class="btn" type="submit">Simpan</button>
    <a class="btn gray" href="{{ route('admin.operations.pilgrims.index') }}">Batal</a>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var departureSelect = document.getElementById('pilgrim-departure');
  var roomSelect = document.getElementById('pilgrim-room-type');
  var hajiFields = document.querySelector('.haji-fields');
  if (!departureSelect) return;

  var hajiIds = @json($departures->where('program_kind', 'haji')->pluck('id')->values());

  function syncHajiFields() {
    var id = parseInt(departureSelect.value || '0', 10);
    if (hajiFields) hajiFields.hidden = !hajiIds.includes(id);
  }

  function syncRoomTypes() {
    if (!roomSelect) return;
    var id = parseInt(departureSelect.value || '0', 10);
    var isHaji = hajiIds.includes(id);
    var selected = roomSelect.value;

    Array.from(roomSelect.options).forEach(function (option) {
      if (option.dataset.hajiOnly === '1') {
        option.hidden = !isHaji;
        option.disabled = !isHaji;
      }
    });

    var active = roomSelect.querySelector('option[value="' + selected + '"]');
    if (active && (active.disabled || active.hidden)) {
      var fallback = Array.from(roomSelect.options).find(function (option) {
        return !option.disabled && !option.hidden;
      });
      if (fallback) roomSelect.value = fallback.value;
    }
  }

  function syncDepartureFields() {
    syncHajiFields();
    syncRoomTypes();
  }

  departureSelect.addEventListener('change', syncDepartureFields);
  syncDepartureFields();
})();
</script>
@endpush

@php
  $index = $index ?? 0;
  $hotel = $hotel ?? \App\Support\HajiPlusPage::blankHotel();
  $masterLocation = old('hotels.'.$index.'.master_location', $hotel['master_location'] ?? \App\Models\Hotel::LOCATION_MADINAH);
  $masterName = old('hotels.'.$index.'.master_name', $hotel['master_name'] ?? '');
@endphp
<article class="haji-hotel-admin-card" data-hotel-card>
  <div class="haji-hotel-admin-head">
    <div class="haji-hotel-admin-title">
      @if(!empty($hotel['image']))
        <img class="haji-hotel-admin-logo" src="{{ $hotel['image'] }}" alt="">
      @else
        <span class="haji-hotel-admin-num" data-hotel-num>{{ is_numeric($index) ? ((int) $index + 1) : 1 }}</span>
      @endif
      <div>
        <b>Hotel <span data-hotel-num-label>{{ is_numeric($index) ? ((int) $index + 1) : 1 }}</span></b>
        <small>Dari master · nama &amp; logo otomatis</small>
      </div>
    </div>
    <button class="btn ghost compact haji-hotel-remove" type="button" data-hotel-remove hidden>Hapus</button>
  </div>

  <div class="row2">
    <label>Lokasi
      <select name="hotels[{{ $index }}][master_location]" class="js-searchable" data-placeholder="Pilih lokasi…" data-hotel-location required>
        @foreach($hotelLocations as $key => $label)
          <option value="{{ $key }}" @selected((string) $masterLocation === (string) $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Hotel
      @include('partials.hotel-select', [
        'name' => 'hotels['.$index.'][master_name]',
        'location' => $masterLocation,
        'selected' => $masterName,
        'empty' => 'Pilih hotel…',
        'required' => true,
      ])
    </label>
  </div>

  <div class="row2">
    <label>Jarak / lokasi
      <input name="hotels[{{ $index }}][distance]" value="{{ old('hotels.'.$index.'.distance', $hotel['distance']) }}" required>
    </label>
    <label>Badge
      <input name="hotels[{{ $index }}][badge]" value="{{ old('hotels.'.$index.'.badge', $hotel['badge']) }}" required>
    </label>
  </div>
  <label>Fasilitas <small class="haji-label-hint">satu baris = satu item</small>
    <textarea name="hotels[{{ $index }}][features_text]" rows="3" required>{{ old('hotels.'.$index.'.features_text', $hotel['features_text']) }}</textarea>
  </label>
</article>

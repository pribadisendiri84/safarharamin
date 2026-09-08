@php
  $index = $index ?? 0;
  $hotel = $hotel ?? \App\Support\HajiPlusPage::blankHotel();
  $source = old('hotels.'.$index.'.source', $hotel['source'] ?? 'custom');
  $masterLocation = old('hotels.'.$index.'.master_location', $hotel['master_location'] ?? \App\Models\Hotel::LOCATION_MADINAH);
  $masterName = old('hotels.'.$index.'.master_name', $hotel['master_name'] ?? '');
  $isMaster = $source === 'master';
@endphp
<div class="haji-admin-card haji-hotel-card" data-hotel-card>
  <div class="haji-hotel-card-head">
    <b>Kartu hotel <span data-hotel-num>{{ is_numeric($index) ? ((int) $index + 1) : 1 }}</span></b>
    <button class="btn gray haji-hotel-remove" type="button" data-hotel-remove>Hapus</button>
  </div>

  <div class="haji-hotel-source">
    <label class="check">
      <input type="radio" name="hotels[{{ $index }}][source]" value="master" @checked($isMaster) data-hotel-source="master">
      Ambil dari master hotel
    </label>
    <label class="check">
      <input type="radio" name="hotels[{{ $index }}][source]" value="custom" @checked(! $isMaster) data-hotel-source="custom">
      Pengaturan khusus Haji
    </label>
  </div>

  <div class="haji-hotel-master-fields" data-hotel-master @unless($isMaster) hidden @endunless>
    <div class="row2">
      <label>Lokasi master
        <select name="hotels[{{ $index }}][master_location]" class="js-searchable" data-placeholder="Pilih lokasi…" data-hotel-location>
          @foreach($hotelLocations as $key => $label)
            <option value="{{ $key }}" @selected((string) $masterLocation === (string) $key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Hotel master
        @include('partials.hotel-select', [
          'name' => 'hotels['.$index.'][master_name]',
          'location' => $masterLocation,
          'selected' => $masterName,
          'empty' => 'Pilih hotel…',
          'required' => false,
        ])
      </label>
    </div>
    <p class="sub">Logo hotel master dipakai otomatis jika foto khusus kosong.</p>
  </div>

  <p class="sub"><b>Pengaturan tampilan halaman Haji</b> — jarak, badge, dan fasilitas khusus program ini.</p>
  <div class="row2">
    <label>Kota
      <input name="hotels[{{ $index }}][city]" value="{{ old('hotels.'.$index.'.city', $hotel['city']) }}" required>
    </label>
    <label>Judul kartu
      <input name="hotels[{{ $index }}][title]" value="{{ old('hotels.'.$index.'.title', $hotel['title']) }}" required>
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
  <label>Fasilitas (satu baris satu item)
    <textarea name="hotels[{{ $index }}][features_text]" rows="3" required>{{ old('hotels.'.$index.'.features_text', $hotel['features_text']) }}</textarea>
  </label>
  <label>Foto khusus (opsional, menimpa logo master)
    <input type="file" name="hotels[{{ $index }}][image_file]" accept="image/*">
  </label>
  @if(!empty($hotel['image']))
    <p class="sub"><img class="haji-admin-thumb" src="{{ $hotel['image'] }}" alt="{{ $hotel['title'] }}"></p>
  @endif
</div>

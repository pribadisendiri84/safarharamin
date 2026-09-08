@php
  $index = $index ?? 0;
  $room = $room ?? [];
  $roomPrice = (int) ($room['price'] ?? 0);
@endphp
<article class="haji-room-card">
  <div class="haji-room-card-top">
    <span class="haji-room-key">{{ $room['label'] ?? 'Kamar' }}</span>
    @if(!empty($room['image']))
      <img class="haji-room-preview" src="{{ $room['image'] }}" alt="">
    @endif
  </div>
  <div class="row2">
    <label>Nama tipe
      <input name="rooms[{{ $index }}][label]" value="{{ old('rooms.'.$index.'.label', $room['label']) }}" required>
    </label>
    <label>Kapasitas
      <input name="rooms[{{ $index }}][occupancy]" value="{{ old('rooms.'.$index.'.occupancy', $room['occupancy']) }}" required>
    </label>
  </div>
  <div class="row2">
    <label>Harga (Rp) <small class="haji-label-hint">nominal penuh per jamaah</small>
      <input
        type="text"
        class="js-rupiah"
        name="rooms[{{ $index }}][price]"
        value="{{ old('rooms.'.$index.'.price', $roomPrice > 0 ? $roomPrice : '') }}"
        required
      >
    </label>
    <label>Satuan
      <input name="rooms[{{ $index }}][price_note]" value="{{ old('rooms.'.$index.'.price_note', $room['price_note'] ?? '/jamaah') }}" required>
    </label>
  </div>
  @if($roomPrice > 0 || !empty($room['price_label']))
    <p class="haji-inline-note">Tampilan web: <strong>{{ $room['price_label'] ?? \App\Support\HajiPlusPage::formatRoomPrice($roomPrice) }}</strong></p>
  @endif
  <div class="haji-room-card-foot">
    <label class="check">
      <input type="checkbox" name="rooms[{{ $index }}][is_featured]" value="1" @checked(old('rooms.'.$index.'.is_featured', $room['is_featured']) === '1')>
      Paling favorit
    </label>
    <label class="haji-file-pick">
      <span>Ganti foto</span>
      <input type="file" name="rooms[{{ $index }}][image_file]" accept="image/*">
    </label>
  </div>
</article>

@php
  $index = $index ?? 0;
  $room = $room ?? [];
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
    <label>Harga
      <input name="rooms[{{ $index }}][price_label]" value="{{ old('rooms.'.$index.'.price_label', $room['price_label']) }}" required>
    </label>
    <label>Satuan
      <input name="rooms[{{ $index }}][price_note]" value="{{ old('rooms.'.$index.'.price_note', $room['price_note']) }}" required>
    </label>
  </div>
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

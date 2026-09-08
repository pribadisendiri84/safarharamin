@php
  use App\Support\HajiExchangeRate;
  use App\Support\HajiPlusPage;

  $index = $index ?? 0;
  $room = $room ?? [];
  $roomPriceUsd = (int) ($room['price'] ?? 0);
  $roomImage = $room['image'] ?? '';
  $idrEstimate = $roomPriceUsd > 0 ? HajiPlusPage::estimateIdrFromUsd($roomPriceUsd) : null;
  $exchangeReady = HajiExchangeRate::enabled() && HajiExchangeRate::currency() === 'USD' && HajiExchangeRate::storedRate();
@endphp
<article class="haji-room-card" data-room-card>
  <div class="haji-room-card-head">
    <span class="haji-room-key">{{ $room['label'] ?? 'Kamar' }}</span>
    <label class="check haji-room-featured-check">
      <input type="checkbox" name="rooms[{{ $index }}][is_featured]" value="1" @checked(old('rooms.'.$index.'.is_featured', $room['is_featured']) === '1')>
      Paling favorit
    </label>
  </div>

  <div class="haji-room-photo" data-room-photo>
    @if(filled($roomImage))
      <img class="haji-room-photo-preview" data-room-photo-img src="{{ $roomImage }}" alt="Foto {{ $room['label'] ?? 'kamar' }}">
      <span class="haji-room-photo-empty" data-room-photo-empty hidden>Foto kamar</span>
    @else
      <img class="haji-room-photo-preview" data-room-photo-img src="" alt="" hidden>
      <span class="haji-room-photo-empty" data-room-photo-empty>Foto kamar</span>
    @endif
    <label class="master-logo-upload haji-room-photo-upload" aria-label="Unggah foto kamar">
      <input
        type="file"
        name="rooms[{{ $index }}][image_file]"
        accept="image/png,image/jpeg,image/webp"
        data-room-photo-input
      >
      @include('admin.partials.icon', ['name' => 'upload'])
    </label>
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
    <label>Harga (USD) <small class="haji-label-hint">nominal flyer per jamaah, contoh 16750</small>
      <input
        type="number"
        name="rooms[{{ $index }}][price]"
        value="{{ old('rooms.'.$index.'.price', $roomPriceUsd > 0 ? $roomPriceUsd : '') }}"
        min="1"
        step="1"
        required
      >
    </label>
    <label>Satuan
      <input name="rooms[{{ $index }}][price_note]" value="{{ old('rooms.'.$index.'.price_note', $room['price_note'] ?? '/jamaah') }}" required>
    </label>
  </div>
  @if($roomPriceUsd > 0 || !empty($room['price_label']))
    <p class="haji-inline-note">
      Tampilan web:
      <strong>{{ $room['price_label'] ?? HajiPlusPage::formatUsdPrice($roomPriceUsd) }}</strong>
      @if($idrEstimate)
        · estimasi <strong>{{ HajiPlusPage::formatIdrEstimate($idrEstimate) }}</strong>
      @elseif(! $exchangeReady)
        · <span class="haji-inline-note-muted">aktifkan kurs USD di Pengaturan untuk estimasi Rp</span>
      @endif
    </p>
    <p class="haji-inline-note haji-inline-note-muted">Acuan jamaah (operasi): nominal Rp dari estimasi kurs saat keberangkatan dibuat.</p>
  @endif
</article>

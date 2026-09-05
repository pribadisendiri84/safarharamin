@php
  /** @var \App\Models\Package $package */
  $departureDate = $package->departure_date?->translatedFormat('d M Y') ?? 'Jadwal menyusul';
  $startingRoom = $package->startingRoomLabel();
  $roomOptions = $package->roomRangeLabel();
  $priceUnit = '/jamaah';
  if ($package->hasMultipleRoomPrices() && $startingRoom) {
      $priceUnit .= ' - '.$startingRoom;
  }
@endphp

<a class="card {{ $package->isFullbook() ? 'is-fullbook' : 'is-available' }}{{ $package->is_hot && ! $package->isFullbook() ? ' is-hot' : '' }}" href="{{ route('packages.show', $package) }}">
  <div class="card-img">
    <img src="{{ $package->coverImage() }}" alt="{{ $package->title }}" loading="lazy">
    @if($package->isFullbook())
      <div class="card-status-banner is-fullbook">
        <i class="bi bi-people-fill"></i>
        <div>
          <strong>Kuota penuh</strong>
          <small>Terima kasih atas kepercayaan jamaah</small>
        </div>
      </div>
    @elseif($package->is_hot)
      <div class="card-status-banner is-hot">
        <i class="bi bi-hourglass-split"></i>
        <div>
          <strong>Kuota terbatas!</strong>
          <small>Ayo amankan seat sebelum kehabisan</small>
        </div>
      </div>
    @endif
  </div>
  <div class="card-body">
    <div class="price-stack">
      @if($package->hasMultipleRoomPrices())
        <span class="price-label">Mulai</span>
      @endif
      <p class="price-line">
        <span class="price-currency">Rp</span>
        <span class="price-amount">{{ number_format((int) $package->price, 0, ',', '.') }}</span>
        <span class="price-unit">{{ $priceUnit }}</span>
      </p>
      @if($package->formattedOriginalPrice())
        <p class="price-was">
          <span class="price-currency">Rp</span>
          <span class="price-amount">{{ number_format((int) $package->original_price, 0, ',', '.') }}</span>
        </p>
      @endif
    </div>
    <h3>{{ $package->title }}</h3>
    <small>{{ $package->catalogTypeLine() }}</small>
    <ul class="card-details">
      <li>
        <span class="meta-ico tone-blue"><i class="bi bi-calendar3"></i></span>
        <span>{{ $departureDate }}</span>
      </li>
      <li>
        <span class="meta-ico tone-green"><i class="bi bi-geo-alt"></i></span>
        <span>{{ $package->cityLabel() }}</span>
      </li>
      <li>
        <span class="meta-ico tone-gold"><i class="bi bi-buildings"></i></span>
        <span class="hotel-stars-rating" aria-label="{{ $package->displayHotelStars() }} dari 5 bintang">
          @for($i = 1; $i <= 5; $i++)
            <i class="bi {{ $i <= $package->displayHotelStars() ? 'bi-star-fill is-filled' : 'bi-star' }}"></i>
          @endfor
        </span>
      </li>
      <li>
        <span class="meta-ico tone-blue"><i class="bi bi-clock"></i></span>
        <span>{{ $package->duration_days }} hari</span>
      </li>
      <li>
        <span class="meta-ico tone-blue"><i class="bi bi-airplane"></i></span>
        <span>{{ $package->airline ?: 'Maskapai berizin' }}</span>
      </li>
      <li>
        <span class="meta-ico tone-rose"><i class="bi bi-ticket-perforated"></i></span>
        <span>{{ $package->seatsLine() }}</span>
      </li>
    </ul>
    @if($roomOptions !== '')
      <p class="card-room-row">
        <span class="meta-ico tone-amber"><i class="bi bi-people"></i></span>
        <span>{{ $roomOptions }}</span>
      </p>
    @endif
    <p class="card-foot">Lihat detail <i class="bi bi-chevron-right"></i></p>
  </div>
</a>

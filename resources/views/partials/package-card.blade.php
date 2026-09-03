@php
  /** @var \App\Models\Package $package */
  $departureDate = $package->departure_date?->translatedFormat('d M Y') ?? 'Jadwal menyusul';
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
      @if(count($package->roomPriceList()) > 1)
        <span class="price-label">Mulai</span>
        <p class="price-rooms">{{ $package->roomRangeLabel() }}</p>
      @endif
      <p class="price-line">
        <span class="price-currency">Rp</span>
        <span class="price-amount">{{ number_format((int) $package->price, 0, ',', '.') }}</span>
        <span class="price-unit">/jamaah</span>
      </p>
      @if($package->formattedOriginalPrice())
        <p class="price-was">
          <span class="price-currency">Rp</span>
          <span class="price-amount">{{ number_format((int) $package->original_price, 0, ',', '.') }}</span>
        </p>
      @endif
    </div>
    <h3>{{ $package->title }}</h3>
    <p class="card-meta-row">
      <span><span class="meta-ico tone-blue"><i class="bi bi-calendar3"></i></span> {{ $departureDate }}</span>
      <span><span class="meta-ico tone-green"><i class="bi bi-geo-alt"></i></span> {{ $package->cityLabel() }}</span>
    </p>
    <ul class="specs specs-icons">
      <li>
        <span class="spec-ico tone-gold"><i class="bi bi-buildings"></i></span>
        <span class="hotel-stars-rating" aria-label="{{ $package->hotel_stars }} dari 5 bintang">
          @for($i = 1; $i <= 5; $i++)
            <i class="bi {{ $i <= (int) $package->hotel_stars ? 'bi-star-fill is-filled' : 'bi-star' }}"></i>
          @endfor
        </span>
      </li>
      <li>
        <span class="spec-ico tone-violet"><i class="bi bi-building"></i></span>
        <span>{{ $package->typeLabel() }}</span>
      </li>
      <li>
        <span class="spec-ico tone-blue"><i class="bi bi-calendar3"></i></span>
        <span>{{ $package->duration_days }} hari</span>
      </li>
      @if(count($package->roomPriceList()) === 1)
      <li>
        <span class="spec-ico tone-amber"><i class="bi bi-people"></i></span>
        <span>{{ $package->roomRangeLabel() }}</span>
      </li>
      @endif
      <li>
        <span class="spec-ico tone-blue"><i class="bi bi-airplane"></i></span>
        <span>{{ $package->airline ?: 'Maskapai berizin' }}</span>
      </li>
      <li>
        <span class="spec-ico tone-rose"><i class="bi bi-ticket-perforated"></i></span>
        <span>{{ $package->seatsLine() }}</span>
      </li>
    </ul>
    <p class="card-foot">Lihat detail <i class="bi bi-chevron-right"></i></p>
  </div>
</a>

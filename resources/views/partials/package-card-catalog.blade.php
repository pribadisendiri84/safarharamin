@php
  /** @var \App\Models\Package $package */
  $departureDate = $package->catalogDepartureDateLine();
  $roomPrices = $package->roomPriceList();
  $airline = $catalogAirlines->get($package->airline);
  $makkahHotel = $catalogHotels->get(\App\Models\Hotel::LOCATION_MAKKAH.'|'.$package->hotel_makkah);
  $madinahHotel = $catalogHotels->get(\App\Models\Hotel::LOCATION_MADINAH.'|'.$package->hotel_madinah);
  $catalogTitle = $package->catalogTitle();
  $titleParts = $package->catalogTitleParts();
  $titleMain = trim(implode(' ', array_filter([$titleParts['type'], $titleParts['kind'] ?: null])));
@endphp

<a class="card catalog-card {{ $package->isFullbook() ? 'is-fullbook' : 'is-available' }}{{ $package->is_hot && ! $package->isFullbook() ? ' is-hot' : '' }}" href="{{ route('packages.show', $package) }}">
  <div class="catalog-card-image">
    <img
      class="catalog-cover{{ $package->hasDedicatedCover() ? '' : ' is-flyer-source' }}"
      src="{{ $package->coverImage() }}"
      alt="{{ $catalogTitle }}"
      loading="lazy"
    >
    @if($package->formattedOriginalPrice())
      <span class="catalog-price-was">{{ $package->formattedOriginalPrice() }}</span>
    @endif
    @if($package->isFullbook())
      <span class="catalog-status is-fullbook"><i class="bi bi-people-fill"></i> Kuota penuh</span>
    @else
      @include('partials.package-card-badge')
    @endif
  </div>

  <div class="catalog-card-body">
    <h3 class="catalog-card-title">
      <span class="catalog-title-main">{{ $titleMain }}</span>
      @if($titleParts['duration'] !== null)
        <span class="catalog-title-sub">{{ $titleParts['duration'] }}</span>
      @endif
    </h3>

    @if($roomPrices !== [])
      <div class="catalog-room-prices" style="--room-cols: {{ count($roomPrices) }}">
        <div class="catalog-room-prices-labels">
          @foreach($roomPrices as $row)
            <span>{{ $row['label'] }}</span>
          @endforeach
        </div>
        <div class="catalog-room-prices-values">
          @foreach($roomPrices as $row)
            <span>{{ $package->formattedMoneyShort($row['price']) }}</span>
          @endforeach
        </div>
      </div>
    @elseif($package->hasListedPrice())
      <div class="catalog-price">
        <p>
          <span class="catalog-price-currency">Rp</span>
          <strong>{{ number_format((int) $package->price, 0, ',', '.') }}</strong>
          <span class="catalog-price-unit">/jamaah</span>
        </p>
      </div>
    @elseif($label = $package->displayPriceLabel())
      <div class="catalog-price-label">
        <span class="price-label-text">{{ $label }}</span>
      </div>
    @endif

    <div class="catalog-airline">
      <span class="catalog-brand-mark">
        @if($airline?->logo)
          <img src="{{ $airline->logo }}" alt="">
        @else
          <i class="bi bi-airplane"></i>
        @endif
      </span>
      <span>
        <small>Maskapai</small>
        <b>{{ $package->airline ?: 'Maskapai berizin' }}</b>
      </span>
    </div>

    <div class="catalog-trip-meta{{ $departureDate === null ? ' is-single' : '' }}">
      @if($departureDate !== null)
        <div class="catalog-trip-item">
          <i class="bi bi-calendar3" aria-hidden="true"></i>
          <span>{{ $departureDate }}</span>
        </div>
      @endif
      <div class="catalog-trip-item">
        <i class="bi bi-geo-alt" aria-hidden="true"></i>
        <span>{{ $package->cityLabel() }}</span>
      </div>
    </div>

    @if(filled($package->hotel_makkah) || filled($package->hotel_madinah))
      <div class="catalog-hotels">
        @foreach([
          ['label' => 'Hotel Makkah', 'name' => $package->hotel_makkah, 'setaraf' => $package->hotel_makkah_setaraf, 'hotel' => $makkahHotel],
          ['label' => 'Hotel Madinah', 'name' => $package->hotel_madinah, 'setaraf' => $package->hotel_madinah_setaraf, 'hotel' => $madinahHotel],
        ] as $hotelRow)
          @if(filled($hotelRow['name']))
            <div class="catalog-hotel">
              @if($hotelRow['hotel']?->logo)
                <span class="catalog-hotel-logo-wrap">
                  <img class="catalog-hotel-logo" src="{{ $hotelRow['hotel']->logo }}" alt="{{ $hotelRow['name'] }}">
                </span>
              @else
                <span class="catalog-hotel-mark" aria-hidden="true">
                  <i class="bi bi-buildings"></i>
                </span>
              @endif
              <span class="catalog-hotel-copy">
                <small>{{ $hotelRow['label'] }}</small>
                <b title="{{ $hotelRow['name'] }}">{{ $hotelRow['name'] }}</b>
                <span>
                  @if($hotelRow['setaraf']) Setaraf · @endif
                  @for($i = 1; $i <= (int) ($hotelRow['hotel']?->stars ?? $package->hotel_stars ?? 0); $i++)
                    <i class="bi bi-star-fill"></i>
                  @endfor
                </span>
              </span>
            </div>
          @endif
        @endforeach
      </div>
    @endif
  </div>
</a>

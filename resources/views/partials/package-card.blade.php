@php /** @var \App\Models\Package $package */ @endphp
<a class="card" href="{{ route('packages.show', $package) }}">
  <div class="card-img">
    <img src="{{ $package->coverImage() }}" alt="{{ $package->title }}" loading="lazy">
    <div class="badges">
      @if($package->is_hot)<span class="badge hot">Kuota terbatas</span>@endif
      <span class="badge gold">{{ $package->hotel_stars }}★</span>
      <span class="badge">{{ $package->typeLabel() }}</span>
    </div>
  </div>
  <div class="card-body">
    <div class="price">
      <strong>{{ $package->formattedStartingPrice() }}</strong>
      @if($package->formattedOriginalPrice())
        <s>{{ $package->formattedOriginalPrice() }}</s>
      @endif
    </div>
    <h3>{{ $package->title }}</h3>
    <p class="loc">{{ $package->departureLine() }}</p>
    <ul class="specs">
      <li>{{ $package->duration_days }} hari</li>
      <li>{{ $package->roomRangeLabel() }}</li>
      <li>{{ $package->airline ?: 'Maskapai berizin' }}</li>
      <li>{{ $package->seatsLine() }}</li>
    </ul>
  </div>
</a>

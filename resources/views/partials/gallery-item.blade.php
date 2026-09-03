<figure class="gallery-card">
  <button type="button" class="gallery-shot" data-src="{{ $item->image }}" data-alt="{{ $item->title }}" aria-label="Perbesar {{ $item->title }}">
    <img src="{{ $item->image }}" alt="{{ $item->title }}" loading="lazy">
    <span class="gallery-card-overlay">
      <span class="gallery-card-text">
        <strong>{{ $item->title }}</strong>
        @if($item->caption)<span>{{ $item->caption }}</span>@endif
      </span>
      <i class="bi bi-arrows-fullscreen gallery-card-zoom" aria-hidden="true"></i>
    </span>
  </button>
</figure>

<figure class="gallery-card is-video">
  <div class="gallery-media">
    <button type="button"
      class="gallery-shot gallery-video-trigger"
      data-video="{{ $item->embedUrl() }}"
      data-alt="{{ $item->title }}"
      aria-label="Putar video {{ $item->title }}">
      <img src="{{ $item->displayImage() }}" alt="{{ $item->title }}" loading="lazy">
      <span class="gallery-play-badge" aria-hidden="true"><i class="bi bi-play-fill"></i></span>
      <span class="gallery-card-overlay">
        <span class="gallery-card-text">
          <strong>{{ $item->title }}</strong>
          @if($item->caption)<span>{{ $item->caption }}</span>@endif
        </span>
      </span>
    </button>
    <div class="gallery-inline-video" hidden>
      <iframe title="Video: {{ $item->title }}" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
      <button type="button" class="gallery-video-close" aria-label="Tutup video">×</button>
    </div>
  </div>
</figure>

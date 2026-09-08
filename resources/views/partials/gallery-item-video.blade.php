<figure class="gallery-card is-video">
  <div class="gallery-media">
    <button type="button"
      class="gallery-shot gallery-video-trigger"
      data-video-type="{{ $item->videoType() }}"
      @if($item->isYoutubeVideo())
      data-youtube-id="{{ \App\Support\YoutubeUrl::extractId($item->video_url) }}"
      @else
      data-video-src="{{ $item->videoPlayUrl() }}"
      @endif
      data-poster="{{ $item->displayImage() }}"
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
  </div>
</figure>

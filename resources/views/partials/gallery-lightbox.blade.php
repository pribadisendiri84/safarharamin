<div class="lightbox" id="gallery-lightbox" hidden>
  <button type="button" class="lightbox-close" aria-label="Tutup">×</button>
  <img alt="">
</div>

@once
  @push('scripts')
  <script>
  (function () {
    var lightbox = document.getElementById('gallery-lightbox');
    if (!lightbox) return;
    var lightImg = lightbox.querySelector('img');
    var closeBtn = lightbox.querySelector('.lightbox-close');

    function openGalleryZoom(src, alt) {
      lightImg.src = src;
      lightImg.alt = alt || '';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeGalleryZoom() {
      lightbox.hidden = true;
      lightImg.src = '';
      lightImg.alt = '';
      document.body.style.overflow = '';
    }

    function resetGalleryVideo(card) {
      var trigger = card.querySelector('.gallery-video-trigger');
      var player = card.querySelector('.gallery-inline-video');
      if (!trigger || !player) return;
      var iframe = player.querySelector('iframe');
      if (iframe) iframe.src = '';
      player.hidden = true;
      trigger.hidden = false;
      card.classList.remove('is-playing');
    }

    function stopOtherGalleryVideos(exceptCard) {
      document.querySelectorAll('.gallery-card.is-video.is-playing').forEach(function (card) {
        if (card !== exceptCard) resetGalleryVideo(card);
      });
    }

    function playGalleryVideo(trigger) {
      var card = trigger.closest('.gallery-card');
      var player = card.querySelector('.gallery-inline-video');
      var iframe = player.querySelector('iframe');
      var embed = trigger.dataset.video || '';

      stopOtherGalleryVideos(card);
      iframe.src = embed + (embed.indexOf('?') >= 0 ? '&' : '?') + 'autoplay=1';
      trigger.hidden = true;
      player.hidden = false;
      card.classList.add('is-playing');
    }

    document.addEventListener('click', function (e) {
      var videoTrigger = e.target.closest('.gallery-video-trigger');
      if (videoTrigger) {
        playGalleryVideo(videoTrigger);
        return;
      }

      var videoClose = e.target.closest('.gallery-video-close');
      if (videoClose) {
        resetGalleryVideo(videoClose.closest('.gallery-card'));
        return;
      }

      var photoBtn = e.target.closest('.gallery-shot:not(.gallery-video-trigger)');
      if (photoBtn) {
        openGalleryZoom(photoBtn.dataset.src, photoBtn.dataset.alt);
        return;
      }

      if (lightbox && (e.target === lightbox || e.target === closeBtn)) closeGalleryZoom();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        if (lightbox && !lightbox.hidden) closeGalleryZoom();
        document.querySelectorAll('.gallery-card.is-video.is-playing').forEach(resetGalleryVideo);
      }
    });
  })();
  </script>
  @endpush
@endonce

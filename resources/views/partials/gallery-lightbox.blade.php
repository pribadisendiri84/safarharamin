<div class="lightbox" id="gallery-lightbox" hidden>
  <button type="button" class="lightbox-close" aria-label="Tutup">×</button>
  <img alt="" hidden>
  <div class="lightbox-video" hidden></div>
</div>

@once
  @push('scripts')
  <script>
  (function () {
    var lightbox = document.getElementById('gallery-lightbox');
    if (!lightbox) return;

    var lightImg = lightbox.querySelector('img');
    var videoHost = lightbox.querySelector('.lightbox-video');
    var closeBtn = lightbox.querySelector('.lightbox-close');

    function clearGalleryVideoPlayer() {
      if (!videoHost) return;
      var video = videoHost.querySelector('video');
      if (video) {
        video.pause();
        video.removeAttribute('src');
        video.load();
      }
      var iframe = videoHost.querySelector('iframe');
      if (iframe) iframe.src = '';
      videoHost.innerHTML = '';
      videoHost.hidden = true;
    }

    function closeGalleryLightbox() {
      lightbox.hidden = true;
      lightbox.classList.remove('is-video');
      lightImg.hidden = true;
      lightImg.src = '';
      lightImg.alt = '';
      clearGalleryVideoPlayer();
      document.body.style.overflow = '';
    }

    function openGalleryPhoto(src, alt) {
      clearGalleryVideoPlayer();
      lightbox.classList.remove('is-video');
      lightImg.src = src;
      lightImg.alt = alt || '';
      lightImg.hidden = false;
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function openGalleryVideo(type, src, poster, alt, youtubeId) {
      if (!videoHost) return;

      lightImg.hidden = true;
      lightImg.src = '';
      lightImg.alt = '';
      clearGalleryVideoPlayer();
      lightbox.classList.add('is-video');
      videoHost.hidden = false;

      if (type === 'file') {
        if (!src) return;
        var video = document.createElement('video');
        video.controls = true;
        video.preload = 'none';
        video.playsInline = true;
        if (poster) video.poster = poster;
        video.src = src;
        video.setAttribute('aria-label', alt || 'Video gallery');
        videoHost.appendChild(video);
      } else {
        var id = youtubeId || '';
        if (!id) return;
        var iframe = document.createElement('iframe');
        iframe.title = alt ? 'Video: ' + alt : 'Video gallery';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?rel=0&modestbranding=1&autoplay=1';
        videoHost.appendChild(iframe);
      }

      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    document.addEventListener('click', function (e) {
      var videoTrigger = e.target.closest('.gallery-video-trigger');
      if (videoTrigger) {
        openGalleryVideo(
          videoTrigger.dataset.videoType || 'youtube',
          videoTrigger.dataset.videoSrc || '',
          videoTrigger.dataset.poster || '',
          videoTrigger.dataset.alt || '',
          videoTrigger.dataset.youtubeId || ''
        );
        return;
      }

      var photoBtn = e.target.closest('.gallery-shot:not(.gallery-video-trigger)');
      if (photoBtn) {
        openGalleryPhoto(photoBtn.dataset.src, photoBtn.dataset.alt);
        return;
      }

      if (lightbox && !lightbox.hidden && (e.target === lightbox || e.target === closeBtn)) {
        closeGalleryLightbox();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lightbox && !lightbox.hidden) {
        closeGalleryLightbox();
      }
    });
  })();
  </script>
  @endpush
@endonce

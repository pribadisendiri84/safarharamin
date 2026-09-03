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
      document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.gallery-shot');
      if (btn) {
        openGalleryZoom(btn.dataset.src, btn.dataset.alt);
        return;
      }
      if (e.target === lightbox || e.target === closeBtn) closeGalleryZoom();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !lightbox.hidden) closeGalleryZoom();
    });
  })();
  </script>
  @endpush
@endonce

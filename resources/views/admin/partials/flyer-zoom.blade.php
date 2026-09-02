<div class="flyer-lightbox" id="flyer-lightbox" hidden>
  <button type="button" class="flyer-lightbox-close" aria-label="Tutup">×</button>
  <img alt="Pratinjau flyer">
</div>

@once
  @push('scripts')
  <script>
  (function () {
    var lightbox = document.getElementById('flyer-lightbox');
    if (!lightbox) return;
    var lightImg = lightbox.querySelector('img');
    var closeBtn = lightbox.querySelector('.flyer-lightbox-close');

    function openFlyerZoom(src, alt) {
      lightImg.src = src;
      lightImg.alt = alt || 'Pratinjau flyer';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeFlyerZoom() {
      lightbox.hidden = true;
      lightImg.src = '';
      document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.flyer-zoom');
      if (btn) {
        e.preventDefault();
        var img = btn.querySelector('img');
        openFlyerZoom(btn.dataset.src || (img && img.src), img && img.alt);
        return;
      }
      if (e.target === lightbox || e.target === closeBtn) closeFlyerZoom();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !lightbox.hidden) closeFlyerZoom();
    });
  })();
  </script>
  @endpush
@endonce

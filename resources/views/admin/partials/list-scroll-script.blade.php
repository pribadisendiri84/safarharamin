@once
  @push('scripts')
  <script>
  (function () {
    var root = document.querySelector('[data-preserve-scroll]');
    if (!root) return;

    var scopePath = root.getAttribute('data-preserve-scroll') || window.location.pathname;
    var storageKey = 'admin-scroll:' + scopePath;

    function saveScroll() {
      try {
        sessionStorage.setItem(storageKey, String(Math.round(window.scrollY)));
      } catch (e) {}
    }

    function restoreScroll() {
      try {
        var raw = sessionStorage.getItem(storageKey);
        if (raw === null) return;
        sessionStorage.removeItem(storageKey);
        var top = parseInt(raw, 10);
        if (!Number.isFinite(top) || top <= 0) return;
        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            window.scrollTo(0, top);
          });
        });
      } catch (e) {}
    }

    function shouldSaveForLink(link) {
      if (!link.href || link.target === '_blank') return false;

      var url;
      try {
        url = new URL(link.href, window.location.origin);
      } catch (e) {
        return false;
      }

      if (url.origin !== window.location.origin) return false;
      if (url.pathname === scopePath) return true;

      if (window.location.pathname === scopePath && /^\/admin\/packages\/\d+\/edit$/.test(url.pathname)) {
        return true;
      }

      return false;
    }

    document.addEventListener('click', function (event) {
      var link = event.target.closest('a[href]');
      if (link && shouldSaveForLink(link)) saveScroll();
    });

    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (window.location.pathname !== scopePath) return;

      var method = (form.method || 'get').toLowerCase();
      if (method === 'get' || method === 'post') saveScroll();
    }, true);

    if (window.location.pathname === scopePath) {
      restoreScroll();
      window.addEventListener('pageshow', function (event) {
        if (event.persisted) restoreScroll();
      });
    }
  })();
  </script>
  @endpush
@endonce

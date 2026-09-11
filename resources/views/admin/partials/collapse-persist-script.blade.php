@once
  @push('scripts')
  <script>
  (function () {
    document.querySelectorAll('[data-collapse-key]').forEach(function (el) {
      var key = 'admin-collapse:' + el.dataset.collapseKey;
      var saved = localStorage.getItem(key);
      var defaultOpen = el.dataset.collapseDefault === 'open';

      if (saved === 'open') {
        el.setAttribute('open', '');
      } else if (saved === 'closed') {
        el.removeAttribute('open');
      } else if (defaultOpen) {
        el.setAttribute('open', '');
      }

      el.addEventListener('toggle', function () {
        localStorage.setItem(key, el.open ? 'open' : 'closed');
      });
    });
  })();
  </script>
  @endpush
@endonce

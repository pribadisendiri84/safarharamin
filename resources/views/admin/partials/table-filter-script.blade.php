@once
  @push('scripts')
  <script>
  (function () {
    document.querySelectorAll('.table-filter-form').forEach(function (form) {
      var searchInput = form.querySelector('[data-filter-search]');
      var searchTimer;

      form.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || !form.contains(target)) return;
        if (target.matches('[data-filter-no-auto]')) return;
        if (target.matches('input[type="search"]')) return;
        form.requestSubmit();
      });

      if (searchInput) {
        searchInput.addEventListener('input', function () {
          window.clearTimeout(searchTimer);
          searchTimer = window.setTimeout(function () {
            form.requestSubmit();
          }, 450);
        });

        searchInput.addEventListener('keydown', function (event) {
          if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            form.requestSubmit();
          }
        });
      }
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('.filter-chip')) return;
      document.querySelectorAll('.filter-chip[open]').forEach(function (chip) {
        chip.removeAttribute('open');
      });
    });
  })();
  </script>
  @endpush
@endonce

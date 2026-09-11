@once
  @push('scripts')
  <script>
  (function () {
    var mobileQuery = window.matchMedia('(max-width: 860px)');

    function drawerHasActiveFilters(drawer) {
      if (!drawer) return false;
      if (drawer.querySelector('.filter-chip > summary.is-active')) return true;
      if (drawer.querySelector('.filter-toggle input:checked')) return true;
      return false;
    }

    function syncFilterDrawer(form) {
      var toggle = form.querySelector('[data-filter-toggle]');
      var drawer = form.querySelector('[data-filter-drawer]');
      if (!toggle || !drawer) return;

      if (!mobileQuery.matches) {
        drawer.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        return;
      }

      var open = drawer.classList.contains('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    document.querySelectorAll('.table-filter-form').forEach(function (form) {
      var searchInput = form.querySelector('[data-filter-search]');
      var searchTimer;
      var toggle = form.querySelector('[data-filter-toggle]');
      var drawer = form.querySelector('[data-filter-drawer]');

      if (toggle && drawer) {
        if (mobileQuery.matches && drawerHasActiveFilters(drawer)) {
          drawer.classList.add('is-open');
        }

        toggle.addEventListener('click', function () {
          if (!mobileQuery.matches) return;
          drawer.classList.toggle('is-open');
          syncFilterDrawer(form);
        });

        mobileQuery.addEventListener('change', function () {
          syncFilterDrawer(form);
        });

        syncFilterDrawer(form);
      }

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

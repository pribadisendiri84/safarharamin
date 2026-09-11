@once
  @push('scripts')
  <script>
  (function () {
    var bar = document.getElementById('package-bulk-bar');
    var selectAll = document.getElementById('package-select-all');
    var countEl = document.getElementById('package-bulk-count');
    var clearBtn = document.getElementById('package-bulk-clear');
    if (!bar || !countEl) return;

    function boxes() {
      return Array.from(document.querySelectorAll('.package-select'));
    }

    function syncBulkBar() {
      var checked = boxes().filter(function (input) { return input.checked; });
      countEl.textContent = String(checked.length);
      bar.hidden = checked.length === 0;

      if (selectAll) {
        var all = boxes();
        selectAll.checked = all.length > 0 && checked.length === all.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
      }
    }

    boxes().forEach(function (input) {
      input.addEventListener('change', syncBulkBar);
    });

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        boxes().forEach(function (input) {
          input.checked = selectAll.checked;
        });
        syncBulkBar();
      });
    }

    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        boxes().forEach(function (input) { input.checked = false; });
        if (selectAll) {
          selectAll.checked = false;
          selectAll.indeterminate = false;
        }
        syncBulkBar();
      });
    }

    bar.addEventListener('submit', function (event) {
      if (boxes().some(function (input) { return input.checked; })) {
        return;
      }
      event.preventDefault();
    });

    syncBulkBar();
  })();
  </script>
  @endpush
@endonce

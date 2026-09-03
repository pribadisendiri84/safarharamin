@once
  @push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
  <script>
  (function () {
    var token = @json(csrf_token());
    var list = document.getElementById('package-home-sort-list');
    if (!list) return;

    var emptyMessage = 'Belum ada paket beranda. Centang kolom Beranda di tabel bawah.';

    function refreshLabels() {
      Array.from(list.querySelectorAll('.home-sort-item')).forEach(function (el) {
        var small = el.querySelector('.home-sort-meta small');
        if (!small) return;
        var parts = small.textContent.split(' · ');
        var meta = parts.length > 1 ? parts.slice(1).join(' · ') : '';
        var slot = parseInt(el.dataset.homeSort || '0', 10);
        var prefix = slot > 0 ? 'Posisi ' + slot : '';
        small.textContent = meta ? prefix + ' · ' + meta : prefix;
      });
    }

    function ensureEmptyState() {
      if (list.querySelector('.home-sort-item')) return;
      var empty = document.createElement('li');
      empty.className = 'empty-state';
      empty.textContent = emptyMessage;
      list.appendChild(empty);
    }

    function removeEmptyState() {
      var empty = list.querySelector('.empty-state');
      if (empty) empty.remove();
    }

    function buildSortItem(item) {
      var li = document.createElement('li');
      li.className = 'home-sort-item';
      li.dataset.id = String(item.id);
      li.dataset.homeSort = String(item.home_sort || 0);

      var handle = document.createElement('span');
      handle.className = 'drag-handle';
      handle.title = 'Drag untuk ubah urutan';
      handle.textContent = '⋮⋮';

      var thumb;
      if (item.thumb) {
        thumb = document.createElement('img');
        thumb.className = 'thumb';
        thumb.src = item.thumb;
        thumb.alt = item.title;
      } else {
        thumb = document.createElement('span');
        thumb.className = 'thumb thumb-empty';
        thumb.textContent = 'Flyer';
      }

      var meta = document.createElement('span');
      meta.className = 'home-sort-meta';
      var title = document.createElement('b');
      title.textContent = item.title;
      var small = document.createElement('small');
      small.textContent = item.meta || '';
      meta.appendChild(title);
      meta.appendChild(small);

      li.appendChild(handle);
      li.appendChild(thumb);
      li.appendChild(meta);

      return li;
    }

    function syncSortList(data) {
      var id = String(data.id);
      var existing = list.querySelector('[data-id="' + id + '"]');

      if (data.featured && data.item) {
        removeEmptyState();
        if (!existing) {
          list.appendChild(buildSortItem(data.item));
          var added = list.querySelector('[data-id="' + id + '"]');
          if (added) added.dataset.homeSort = String(data.home_sort || 0);
        } else {
          existing.dataset.homeSort = String(data.home_sort || 0);
        }
      } else if (existing) {
        existing.remove();
        ensureEmptyState();
      }

      refreshLabels();
    }

    function flashMessage(text, isError) {
      if (!text) return;
      var el = document.createElement('div');
      el.className = 'alert ' + (isError ? 'err' : 'ok') + ' toast-flash';
      el.textContent = text;
      document.body.appendChild(el);
      setTimeout(function () { el.remove(); }, 4200);
    }

    function parseToggleResponse(response) {
      return response.json().then(function (data) {
        return { response: response, data: data };
      });
    }

    document.querySelectorAll('[data-package-home-toggle]').forEach(function (input) {
      input.addEventListener('change', function () {
        var checked = input.checked;
        var previous = !checked;

        fetch(input.dataset.url, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify({ is_featured: checked ? '1' : '0' }),
        })
          .then(parseToggleResponse)
          .then(function (result) {
            if (!result.response.ok || result.data.ok === false) {
              throw new Error(result.data.message || 'Gagal menyimpan.');
            }
            input.checked = !!result.data.featured;
            syncSortList(result.data);
            flashMessage(result.data.message || '');
          })
          .catch(function (error) {
            input.checked = previous;
            flashMessage(error.message || 'Gagal menyimpan. Coba lagi.', true);
          });
      });
    });

    if (typeof Sortable === 'undefined') return;

    Sortable.create(list, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function () {
        Array.from(list.querySelectorAll('.home-sort-item')).forEach(function (el, index) {
          el.dataset.homeSort = String(index + 1);
        });
        refreshLabels();
        var order = Array.from(list.querySelectorAll('[data-id]')).map(function (el) {
          return parseInt(el.dataset.id, 10);
        });
        fetch(list.dataset.reorderUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify({ order: order }),
        });
      },
    });
  })();
  </script>
  @endpush
@endonce

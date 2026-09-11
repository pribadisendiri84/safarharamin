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
      Array.from(list.querySelectorAll('.home-sort-item')).forEach(function (el, index) {
        var slot = index + 1;
        el.dataset.homeSort = String(slot);
        var small = el.querySelector('.home-sort-meta small');
        if (!small) return;
        var parts = small.textContent.split(' · ');
        var meta = parts.length > 1 ? parts.slice(1).join(' · ') : (parts[0] || '');
        if (/^Posisi \d+/.test(parts[0] || '')) {
          meta = parts.slice(1).join(' · ');
        }
        small.textContent = meta ? 'Posisi ' + slot + ' · ' + meta : 'Posisi ' + slot;
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

    function buildActions(item) {
      var actions = document.createElement('span');
      actions.className = 'home-sort-actions';

      var edit = document.createElement('a');
      edit.className = 'btn gray compact';
      edit.textContent = 'Edit';
      edit.href = item.edit_url || ('/admin/packages/' + item.id + '/edit');

      var remove = document.createElement('button');
      remove.className = 'btn red compact';
      remove.type = 'button';
      remove.textContent = 'Hapus';
      remove.dataset.packageHomeRemove = '1';
      remove.dataset.id = String(item.id);
      remove.dataset.url = item.remove_url || '';

      actions.appendChild(edit);
      actions.appendChild(remove);

      return actions;
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
      var slot = parseInt(String(item.home_sort || '0'), 10);
      var detail = item.meta || '';
      small.textContent = slot > 0 ? 'Posisi ' + slot + (detail ? ' · ' + detail : '') : detail;
      meta.appendChild(title);
      meta.appendChild(small);

      li.appendChild(handle);
      li.appendChild(thumb);
      li.appendChild(meta);
      li.appendChild(buildActions(item));

      return li;
    }

    function syncTableCheckbox(id, checked) {
      var toggle = document.querySelector('[data-package-home-toggle][data-id="' + id + '"]');
      if (toggle) toggle.checked = !!checked;
    }

    function syncSortList(data) {
      var id = String(data.id);
      var existing = list.querySelector('[data-id="' + id + '"]');

      if (data.featured) {
        removeEmptyState();
        if (!existing && data.item) {
          list.appendChild(buildSortItem(data.item));
        } else if (existing) {
          existing.dataset.homeSort = String(data.home_sort || 0);
        }
      } else if (existing) {
        existing.remove();
        ensureEmptyState();
      }

      syncTableCheckbox(id, !!data.featured);
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

    function submitFeaturedToggle(url, featured) {
      return fetch(url, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ is_featured: featured ? '1' : '0' }),
      }).then(parseToggleResponse);
    }

    document.querySelectorAll('[data-package-home-toggle]').forEach(function (input) {
      input.addEventListener('change', function () {
        var checked = input.checked;
        var previous = !checked;

        submitFeaturedToggle(input.dataset.url, checked)
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

    list.addEventListener('click', function (event) {
      var button = event.target.closest('[data-package-home-remove]');
      if (!button) return;

      event.preventDefault();
      event.stopPropagation();

      var id = String(button.dataset.id || '');
      var url = button.dataset.url || '';
      if (!url) return;

      submitFeaturedToggle(url, false)
        .then(function (result) {
          if (!result.response.ok || result.data.ok === false) {
            throw new Error(result.data.message || 'Gagal menghapus dari beranda.');
          }
          syncSortList(result.data);
          flashMessage(result.data.message || 'Paket dihapus dari beranda.');
        })
        .catch(function (error) {
          flashMessage(error.message || 'Gagal menghapus dari beranda.', true);
        });
    });

    if (typeof Sortable === 'undefined') return;

    Sortable.create(list, {
      handle: '.drag-handle',
      animation: 150,
      filter: '.home-sort-actions, .home-sort-actions *',
      preventOnFilter: false,
      onEnd: function () {
        refreshLabels();
        var order = Array.from(list.querySelectorAll('.home-sort-item[data-id]')).map(function (el) {
          return parseInt(el.dataset.id, 10);
        }).filter(function (id) {
          return Number.isFinite(id) && id > 0;
        });

        if (order.length === 0) return;

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

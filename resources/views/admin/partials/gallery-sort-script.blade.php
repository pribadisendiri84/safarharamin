@once
  @push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
  <script>
  (function () {
    var token = @json(csrf_token());
    var homeLimit = {{ \App\Models\GalleryItem::homeLimit() }};

    function postOrder(url, type, order) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ type: type, order: order }),
      });
    }

    function idsFromList(list) {
      return Array.from(list.querySelectorAll('[data-id]')).map(function (el) {
        return parseInt(el.dataset.id, 10);
      });
    }

    function refreshHomeLabels(list) {
      Array.from(list.querySelectorAll('.home-sort-item')).forEach(function (el) {
        var small = el.querySelector('.home-sort-meta small');
        if (!small) return;
        var slot = parseInt(el.dataset.homeSort || '0', 10);
        small.textContent = slot > 0 ? 'Posisi ' + slot : '';
      });
    }

    var homeList = document.getElementById('home-sort-list');
    if (homeList && typeof Sortable !== 'undefined') {
      Sortable.create(homeList, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function () {
          Array.from(homeList.querySelectorAll('.home-sort-item')).forEach(function (el, index) {
            el.dataset.homeSort = String(index + 1);
          });
          refreshHomeLabels(homeList);
          postOrder(homeList.dataset.reorderUrl, 'home', idsFromList(homeList));
        },
      });
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

    function syncGalleryHomeList(data) {
      if (!homeList) return;
      var id = String(data.id);
      var existing = homeList.querySelector('[data-id="' + id + '"]');

      if (data.featured && data.item) {
        var empty = homeList.querySelector('.empty-state');
        if (empty) empty.remove();
        if (!existing) {
          var li = document.createElement('li');
          li.className = 'home-sort-item';
          li.dataset.id = id;
          li.dataset.homeSort = String(data.home_sort || 0);
          li.innerHTML = '<span class="drag-handle" title="Drag untuk ubah urutan">⋮⋮</span>'
            + '<img class="thumb" src="" alt="">'
            + '<span class="home-sort-meta"><b></b><small></small></span>';
          li.querySelector('img').src = data.item.thumb;
          li.querySelector('img').alt = data.item.title;
          li.querySelector('b').textContent = data.item.title;
          homeList.appendChild(li);
        } else {
          existing.dataset.homeSort = String(data.home_sort || 0);
        }
      } else if (existing) {
        existing.remove();
        if (!homeList.querySelector('.home-sort-item')) {
          var placeholder = document.createElement('li');
          placeholder.className = 'empty-state';
          placeholder.textContent = 'Belum ada foto beranda. Centang kolom Beranda di tabel bawah.';
          homeList.appendChild(placeholder);
        }
      }

      refreshHomeLabels(homeList);
    }

    document.querySelectorAll('[data-gallery-home-toggle]').forEach(function (input) {
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
          body: JSON.stringify({ show_on_home: checked ? '1' : '0' }),
        })
          .then(parseToggleResponse)
          .then(function (result) {
            if (!result.response.ok || result.data.ok === false) {
              throw new Error(result.data.message || 'Gagal menyimpan.');
            }
            input.checked = !!result.data.featured;
            syncGalleryHomeList(result.data);
            flashMessage(result.data.message || '');
          })
          .catch(function (error) {
            input.checked = previous;
            flashMessage(error.message || 'Gagal menyimpan. Coba lagi.', true);
          });
      });
    });

    var galleryTable = document.getElementById('gallery-sort-table');
    if (galleryTable && typeof Sortable !== 'undefined') {
      Sortable.create(galleryTable, {
        handle: '.drag-handle',
        animation: 150,
        draggable: 'tr[data-id]',
        onEnd: function () {
          postOrder(galleryTable.dataset.reorderUrl, 'gallery', idsFromList(galleryTable));
        },
      });
    }
  })();
  </script>
  @endpush
@endonce

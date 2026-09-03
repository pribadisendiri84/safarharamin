@once
  @push('scripts')
  <script>
  (function () {
    var token = @json(csrf_token());

    function flashMessage(text, isError) {
      if (!text) return;
      var el = document.createElement('div');
      el.className = 'alert ' + (isError ? 'err' : 'ok') + ' toast-flash';
      el.textContent = text;
      document.body.appendChild(el);
      setTimeout(function () { el.remove(); }, 4200);
    }

    function syncStatusClass(select, status) {
      select.className = 'status-select ' + status;
    }

    document.querySelectorAll('[data-package-status]').forEach(function (select) {
      var previous = select.value;

      select.addEventListener('change', function () {
        var next = select.value;

        fetch(select.dataset.url, {
          method: 'PATCH',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify({ status: next }),
        })
          .then(function (response) {
            return response.json().then(function (data) {
              return { response: response, data: data };
            });
          })
          .then(function (result) {
            if (!result.response.ok || result.data.ok === false) {
              throw new Error(result.data.message || 'Gagal menyimpan status.');
            }
            select.value = result.data.status;
            previous = result.data.status;
            syncStatusClass(select, result.data.status);
            flashMessage(result.data.message || '');
          })
          .catch(function (error) {
            select.value = previous;
            flashMessage(error.message || 'Gagal menyimpan status. Coba lagi.', true);
          });
      });
    });
  })();
  </script>
  @endpush
@endonce

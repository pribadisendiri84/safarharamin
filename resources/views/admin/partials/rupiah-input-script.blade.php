<script>
(function () {
  function digitsOnly(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function formatRupiah(value) {
    var digits = digitsOnly(value);
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function initRupiahInput(input) {
    if (!input || input.dataset.rupiahReady === '1') return;
    input.dataset.rupiahReady = '1';
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('autocomplete', 'off');
    if (input.value) input.value = formatRupiah(input.value);

    input.addEventListener('input', function () {
      input.value = formatRupiah(input.value);
      var len = input.value.length;
      input.setSelectionRange(len, len);
    });

    input.addEventListener('blur', function () {
      input.value = formatRupiah(input.value);
    });
  }

  document.querySelectorAll('.js-rupiah').forEach(initRupiahInput);

  document.querySelectorAll('form').forEach(function (form) {
    if (!form.querySelector('.js-rupiah')) return;
    form.addEventListener('submit', function () {
      form.querySelectorAll('.js-rupiah').forEach(function (input) {
        input.value = digitsOnly(input.value);
      });
    });
  });
})();
</script>

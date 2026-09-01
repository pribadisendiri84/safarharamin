<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
document.querySelectorAll('select.js-searchable').forEach(function (el) {
  if (!el || el.tomselect) return;
  new TomSelect(el, {
    create: false,
    allowEmptyOption: true,
    maxOptions: 500,
    placeholder: el.dataset.placeholder || 'Cari lalu pilih…',
  });
});
</script>

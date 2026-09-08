<script>
(function () {
  document.querySelectorAll('[data-editor-tabs]').forEach(function (nav) {
    var root = nav.closest('.admin-editor') || nav.parentElement;
    if (!root) return;

    var tabs = nav.querySelectorAll('[data-editor-tab]');
    var sections = root.querySelectorAll('[data-editor-section]');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var id = tab.getAttribute('data-editor-tab');
        tabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); });
        sections.forEach(function (section) {
          var active = section.getAttribute('data-editor-section') === id;
          section.classList.toggle('is-active', active);
          section.hidden = !active;
        });
      });
    });
  });
})();
</script>

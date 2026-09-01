<script>
document.querySelectorAll('.password-field').forEach(function (wrap) {
  var input = wrap.querySelector('input');
  var btn = wrap.querySelector('.password-toggle');
  if (!input || !btn) return;
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    btn.title = show ? 'Sembunyikan password' : 'Tampilkan password';
    var eye = btn.querySelector('.eye-show');
    var hide = btn.querySelector('.eye-hide');
    if (eye) eye.hidden = show;
    if (hide) hide.hidden = !show;
  });
});
</script>

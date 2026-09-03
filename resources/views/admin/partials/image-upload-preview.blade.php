@props([
    'inputId',
    'hint' => 'Pratinjau file baru',
    'embedded' => false,
])

@if(! $embedded)
<div class="upload-preview" id="{{ $inputId }}-preview" hidden>
  <p class="sub">{{ $hint }}</p>
  <div class="flyer-previews" id="{{ $inputId }}-preview-grid"></div>
</div>
<p class="sub upload-status" id="{{ $inputId }}-status" hidden></p>
@endif

@once
  @push('scripts')
  <script>
  (function () {
    var MAX_W = 1200;
    var MAX_H = 1700;
    var JPEG_Q = 0.82;

    function kb(size) {
      return Math.max(1, Math.round(size / 1024));
    }

    function targetSize(w, h) {
      if (w <= MAX_W && h <= MAX_H) return [w, h];
      var ratio = Math.min(MAX_W / w, MAX_H / h);
      return [Math.max(1, Math.round(w * ratio)), Math.max(1, Math.round(h * ratio))];
    }

    function setInputFiles(input, file) {
      var dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
    }

    function compressImage(file) {
      if (!file.type.startsWith('image/') || file.type === 'image/gif') {
        return Promise.resolve(file);
      }

      return new Promise(function (resolve) {
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function () {
          URL.revokeObjectURL(url);
          var size = targetSize(img.width, img.height);
          var canvas = document.createElement('canvas');
          canvas.width = size[0];
          canvas.height = size[1];
          canvas.getContext('2d').drawImage(img, 0, 0, size[0], size[1]);
          canvas.toBlob(function (blob) {
            if (!blob) return resolve(file);
            var base = file.name.replace(/\.[^.]+$/, '') || 'foto';
            resolve(new File([blob], base + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
          }, 'image/jpeg', JPEG_Q);
        };
        img.onerror = function () {
          URL.revokeObjectURL(url);
          resolve(file);
        };
        img.src = url;
      });
    }

    document.querySelectorAll('[data-upload-preview]').forEach(function (input) {
      var previewId = input.dataset.uploadPreview;
      var box = document.getElementById(previewId + '-preview');
      var col = document.getElementById(previewId + '-preview-col');
      var grid = document.getElementById(previewId + '-preview-grid');
      var status = document.getElementById(previewId + '-status');
      if (!grid) return;

      var urls = [];

      function setStatus(text) {
        if (!status) return;
        if (!text) {
          status.hidden = true;
          status.textContent = '';
          return;
        }
        status.hidden = false;
        status.textContent = text;
      }

      function clearPreview() {
        urls.forEach(function (url) { URL.revokeObjectURL(url); });
        urls = [];
        grid.innerHTML = '';
        if (box) box.hidden = true;
        if (col) col.hidden = true;
      }

      input.addEventListener('change', function () {
        clearPreview();
        setStatus('');
        var files = Array.from(input.files || []);
        if (!files.length) return;

        input.dataset.compressing = '1';
        setStatus('Mengompres foto…');

        Promise.all(files.map(compressImage)).then(function (ready) {
          if (ready.length === 1) {
            setInputFiles(input, ready[0]);
          } else if (ready.length > 1) {
            var dt = new DataTransfer();
            ready.forEach(function (file) { dt.items.add(file); });
            input.files = dt.files;
          }

          var originalKb = files.reduce(function (sum, f) { return sum + f.size; }, 0);
          var compressedKb = ready.reduce(function (sum, f) { return sum + f.size; }, 0);

          ready.forEach(function (file) {
            if (!file.type.startsWith('image/')) return;
            var url = URL.createObjectURL(file);
            urls.push(url);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'flyer-zoom';
            btn.dataset.src = url;
            var img = document.createElement('img');
            img.className = 'thumb flyer';
            img.alt = file.name;
            img.src = url;
            btn.appendChild(img);
            grid.appendChild(btn);
          });

          var hasPreview = grid.children.length > 0;
          if (box) box.hidden = !hasPreview;
          if (col) col.hidden = !hasPreview;

          if (compressedKb < originalKb) {
            setStatus('Siap unggah: ' + kb(originalKb) + ' KB → ' + kb(compressedKb) + ' KB (maks. ' + MAX_W + '×' + MAX_H + ' px).');
          } else {
            setStatus('Siap unggah (' + kb(compressedKb) + ' KB).');
          }

          input.dataset.compressing = '0';
        }).catch(function () {
          input.dataset.compressing = '0';
          setStatus('');
        });
      });

      var form = input.closest('form');
      if (form) {
        form.addEventListener('submit', function (event) {
          if (input.dataset.compressing === '1') {
            event.preventDefault();
            setStatus('Tunggu sebentar, foto masih dikompres…');
          }
        });
      }
    });
  })();
  </script>
  @endpush
@endonce

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
@endif

@once
  @push('scripts')
  <script>
  document.querySelectorAll('[data-upload-preview]').forEach(function (input) {
    var previewId = input.dataset.uploadPreview;
    var box = document.getElementById(previewId + '-preview');
    var col = document.getElementById(previewId + '-preview-col');
    var grid = document.getElementById(previewId + '-preview-grid');
    if (!grid) return;

    var urls = [];

    function clearPreview() {
      urls.forEach(function (url) { URL.revokeObjectURL(url); });
      urls = [];
      grid.innerHTML = '';
      if (box) box.hidden = true;
      if (col) col.hidden = true;
    }

    input.addEventListener('change', function () {
      clearPreview();
      var files = Array.from(input.files || []);
      if (!files.length) return;

      files.forEach(function (file) {
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
    });
  });
  </script>
  @endpush
@endonce

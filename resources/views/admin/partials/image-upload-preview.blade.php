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
    var grid = document.getElementById(previewId + '-preview-grid');
    var empty = document.getElementById(previewId + '-empty');
    if (!grid) return;

    var urls = [];

    function clearPreview() {
      urls.forEach(function (url) { URL.revokeObjectURL(url); });
      urls = [];
      grid.innerHTML = '';
      if (box) box.hidden = true;
      if (empty) empty.hidden = false;
    }

    input.addEventListener('change', function () {
      clearPreview();
      var files = Array.from(input.files || []);
      if (!files.length) return;

      files.forEach(function (file) {
        if (!file.type.startsWith('image/')) return;
        var url = URL.createObjectURL(file);
        urls.push(url);
        var img = document.createElement('img');
        img.className = 'thumb large';
        img.alt = file.name;
        img.src = url;
        grid.appendChild(img);
      });

      var hasPreview = grid.children.length > 0;
      if (box) box.hidden = !hasPreview;
      if (empty) empty.hidden = hasPreview;
    });
  });
  </script>
  @endpush
@endonce

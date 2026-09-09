@props(['cover' => null])

<div class="master-cover-field" data-cover-field>
  <img
    class="master-cover-preview"
    src="{{ $cover }}"
    alt="Cover katalog"
    @unless(filled($cover)) hidden @endunless
    data-cover-preview
  >
  <span class="master-cover-empty" @if(filled($cover)) hidden @endif data-cover-empty>Belum ada cover</span>
  <label class="master-logo-upload" aria-label="Upload cover katalog">
    <input type="file" name="cover_photo" accept="image/png,image/jpeg,image/webp,image/*" data-cover-input>
    @include('admin.partials.icon', ['name' => 'upload'])
  </label>
  @if(filled($cover))
    <label class="check master-cover-remove">
      <input type="checkbox" name="remove_cover" value="1" @checked(old('remove_cover')) data-cover-remove>
      Hapus cover
    </label>
  @endif
</div>

@once
  @push('scripts')
  <script>
  (function () {
    document.querySelectorAll('[data-cover-field]').forEach(function (field) {
      var preview = field.querySelector('[data-cover-preview]');
      var empty = field.querySelector('[data-cover-empty]');
      var input = field.querySelector('[data-cover-input]');
      var remove = field.querySelector('[data-cover-remove]');
      var previewUrl = null;

      function revokePreviewUrl() {
        if (previewUrl) {
          URL.revokeObjectURL(previewUrl);
          previewUrl = null;
        }
      }

      function showPreview(src) {
        if (!preview || !empty) return;
        preview.src = src;
        preview.hidden = false;
        empty.hidden = true;
      }

      function showEmpty() {
        if (!preview || !empty) return;
        preview.hidden = true;
        empty.hidden = false;
      }

      if (input) {
        input.addEventListener('change', function () {
          revokePreviewUrl();
          var file = (input.files || [])[0];
          if (!file || !file.type.startsWith('image/')) {
            if (remove && remove.checked) {
              showEmpty();
            }
            return;
          }
          if (remove) remove.checked = false;
          previewUrl = URL.createObjectURL(file);
          showPreview(previewUrl);
        });
      }

      if (remove) {
        remove.addEventListener('change', function () {
          if (remove.checked) {
            revokePreviewUrl();
            if (input) input.value = '';
            showEmpty();
            return;
          }
          if (preview && preview.src && !preview.hidden) {
            showPreview(preview.src);
          }
        });
      }
    });
  })();
  </script>
  @endpush
@endonce

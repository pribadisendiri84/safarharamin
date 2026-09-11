@props([
  'cover' => null,
])

<fieldset class="package-media-fieldset">
  <legend>Cover katalog default</legend>
  <p class="sub">Dipakai otomatis untuk paket baru dari sync Arminareka jika cover paket masih kosong.</p>

  <div class="package-media-grid package-media-grid--single">
    <div class="package-media-slot" data-media-slot="cover">
      <p class="package-media-label">Cover Katalog</p>
      <div class="package-media-frame package-media-frame--landscape">
        <img
          class="package-media-preview"
          src="{{ $cover }}"
          alt="Cover katalog default"
          @unless(filled($cover)) hidden @endunless
          data-media-preview-img
        >
        <span class="package-media-empty" @if(filled($cover)) hidden @endif data-media-empty>Belum ada cover</span>
      </div>
      <div class="package-media-actions">
        <button class="btn gray compact" type="button" data-media-preview-btn @unless(filled($cover)) disabled @endunless>Preview</button>
        <label class="btn gray compact package-media-replace">
          Unggah
          <input
            type="file"
            name="cover_photo"
            accept="image/png,image/jpeg,image/webp,image/*"
            hidden
            data-media-input
          >
        </label>
        <button
          class="package-media-remove"
          type="button"
          aria-label="Hapus cover"
          title="Hapus cover"
          @unless(filled($cover)) hidden @endunless
          data-media-remove-btn
        >×</button>
      </div>
      <input type="hidden" name="remove_cover" value="{{ old('remove_cover') ? '1' : '0' }}" data-media-remove-flag>
      <p class="sub package-media-hint">Landscape 2:1 — contoh 1200×600 px</p>
    </div>
  </div>
</fieldset>

@include('admin.partials.flyer-zoom')

@once
  @push('scripts')
  <script>
  (function () {
    function openMediaPreview(src, alt) {
      var lightbox = document.getElementById('flyer-lightbox');
      if (!lightbox || !src) return;
      var img = lightbox.querySelector('img');
      img.src = src;
      img.alt = alt || 'Pratinjau cover';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('[data-media-slot="cover"]').forEach(function (slot) {
      var previewImg = slot.querySelector('[data-media-preview-img]');
      var empty = slot.querySelector('[data-media-empty]');
      var input = slot.querySelector('[data-media-input]');
      var previewBtn = slot.querySelector('[data-media-preview-btn]');
      var removeBtn = slot.querySelector('[data-media-remove-btn]');
      var removeFlag = slot.querySelector('[data-media-remove-flag]');
      var previewUrl = null;

      function revokePreviewUrl() {
        if (previewUrl) {
          URL.revokeObjectURL(previewUrl);
          previewUrl = null;
        }
      }

      function setRemoveFlag(active) {
        if (removeFlag) removeFlag.value = active ? '1' : '0';
      }

      function showPreview(src) {
        if (!previewImg || !empty) return;
        previewImg.src = src;
        previewImg.hidden = false;
        empty.hidden = true;
        if (previewBtn) previewBtn.disabled = false;
        if (removeBtn) removeBtn.hidden = false;
      }

      function showEmpty() {
        if (!previewImg || !empty) return;
        previewImg.hidden = true;
        empty.hidden = false;
        if (previewBtn) previewBtn.disabled = true;
        if (removeBtn) removeBtn.hidden = true;
      }

      if (previewBtn) {
        previewBtn.addEventListener('click', function () {
          if (previewImg && !previewImg.hidden && previewImg.src) {
            openMediaPreview(previewImg.src, previewImg.alt);
          }
        });
      }

      if (input) {
        input.addEventListener('change', function () {
          revokePreviewUrl();
          var file = (input.files || [])[0];
          if (!file || !file.type.startsWith('image/')) return;
          setRemoveFlag(false);
          previewUrl = URL.createObjectURL(file);
          showPreview(previewUrl);
        });
      }

      if (removeBtn) {
        removeBtn.addEventListener('click', function () {
          revokePreviewUrl();
          if (input) input.value = '';
          setRemoveFlag(true);
          showEmpty();
        });
      }
    });
  })();
  </script>
  @endpush
@endonce

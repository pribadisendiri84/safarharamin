@props([
    'cover' => null,
    'flyers' => [],
])

@php
  $flyer = ($flyers ?? [])[0] ?? null;
@endphp

<fieldset class="package-media-fieldset itinerary-pdf-fieldset">
  <legend>Media Paket</legend>
  <p class="sub">Cover untuk kartu katalog; flyer untuk halaman detail paket. Kosongkan cover = flyer dipakai sebagai fallback di katalog.</p>

  <div class="package-media-grid">
    <div class="package-media-slot" data-media-slot="cover">
      <p class="package-media-label">Cover Katalog</p>
      <div class="package-media-frame package-media-frame--landscape">
        <img
          class="package-media-preview"
          src="{{ $cover }}"
          alt="Cover katalog"
          @unless(filled($cover)) hidden @endunless
          data-media-preview-img
        >
        <span class="package-media-empty" @if(filled($cover)) hidden @endif data-media-empty>Belum ada cover</span>
      </div>
      <div class="package-media-actions">
        <button class="btn gray compact" type="button" data-media-preview-btn @unless(filled($cover)) disabled @endunless>Preview</button>
        <label class="btn gray compact package-media-replace">
          Ganti
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

    <div class="package-media-slot" data-media-slot="flyer">
      <p class="package-media-label">Flyer Paket</p>
      <div class="package-media-frame package-media-frame--portrait">
        <img
          class="package-media-preview"
          src="{{ $flyer }}"
          alt="Flyer paket"
          @unless(filled($flyer)) hidden @endunless
          data-media-preview-img
        >
        <span class="package-media-empty" @if(filled($flyer)) hidden @endif data-media-empty>Belum ada flyer</span>
      </div>
      <div class="package-media-actions">
        <button class="btn gray compact" type="button" data-media-preview-btn @unless(filled($flyer)) disabled @endunless>Preview</button>
        <label class="btn gray compact package-media-replace">
          Ganti
          <input
            type="file"
            id="flyer-upload"
            name="photos[]"
            accept="image/*"
            hidden
            data-media-input
            data-upload-preview="flyer-upload"
          >
        </label>
        <button
          class="package-media-remove"
          type="button"
          aria-label="Hapus flyer"
          title="Hapus flyer"
          @unless(filled($flyer)) hidden @endunless
          data-media-remove-btn
        >×</button>
      </div>
      <input type="hidden" name="remove_flyer" value="{{ old('remove_flyer') ? '1' : '0' }}" data-media-remove-flag>
      <p class="sub package-media-hint">Portrait · A4 / 3:4 — otomatis dikecilkan sebelum unggah (maks. 1200×1700 px)</p>
      <p class="sub upload-status" id="flyer-upload-status" hidden></p>
      <div id="flyer-upload-preview-grid" hidden aria-hidden="true"></div>
    </div>
  </div>
</fieldset>

@include('admin.partials.flyer-zoom')
@include('admin.partials.image-upload-preview', ['inputId' => 'flyer-upload', 'embedded' => true])

@once
  @push('scripts')
  <script>
  (function () {
    function openMediaPreview(src, alt) {
      var lightbox = document.getElementById('flyer-lightbox');
      if (!lightbox || !src) return;
      var img = lightbox.querySelector('img');
      img.src = src;
      img.alt = alt || 'Pratinjau media';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('[data-media-slot]').forEach(function (slot) {
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

      function currentPreviewSrc() {
        if (previewImg && !previewImg.hidden && previewImg.src) {
          return previewImg.src;
        }
        return '';
      }

      if (previewBtn) {
        previewBtn.addEventListener('click', function () {
          openMediaPreview(currentPreviewSrc(), previewImg && previewImg.alt);
        });
      }

      if (input) {
        input.addEventListener('change', function () {
          revokePreviewUrl();
          var file = (input.files || [])[0];
          if (!file || !file.type.startsWith('image/')) {
            return;
          }
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

      if (input && input.dataset.uploadPreview) {
        input.addEventListener('change', function () {
          var grid = document.getElementById(input.dataset.uploadPreview + '-preview-grid');
          if (!grid) return;
          var observer = new MutationObserver(function () {
            var thumb = grid.querySelector('img');
            if (thumb && thumb.src) {
              setRemoveFlag(false);
              showPreview(thumb.src);
            }
          });
          observer.observe(grid, { childList: true, subtree: true });
        });
      }
    });
  })();
  </script>
  @endpush
@endonce

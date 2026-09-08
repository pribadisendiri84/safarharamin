@php
  use App\Support\HajiPlusPage;

  $namePrefix = $namePrefix ?? 'benefits[0]';
  $icon = $icon ?? '';
  $isUpload = HajiPlusPage::iconIsUploaded($icon);
  $fieldKey = str_replace(['[', ']'], ['.', ''], $namePrefix);
  $iconClass = old($fieldKey.'.icon', $isUpload ? '' : HajiPlusPage::iconBootstrapClass($icon));
@endphp
<div class="haji-icon-field">
  <label>Ikon Bootstrap <small class="haji-label-hint">opsional jika pakai upload</small>
    <input
      name="{{ $namePrefix }}[icon]"
      value="{{ $iconClass }}"
      placeholder="bi-building"
      data-haji-icon-class
    >
  </label>
  <div class="haji-icon-upload-row">
    <div class="haji-icon-preview-wrap" data-haji-icon-preview>
      @if($isUpload)
        <img class="haji-icon-preview" src="{{ $icon }}" alt="">
      @else
        <span class="haji-icon-preview haji-icon-preview--bi" aria-hidden="true">
          <i class="bi {{ $iconClass ?: 'bi-circle' }}"></i>
        </span>
      @endif
    </div>
    <label class="master-logo-upload haji-icon-upload-btn" aria-label="Upload ikon">
      <input type="file" name="{{ $namePrefix }}[icon_file]" accept="image/png,image/jpeg,image/webp,image/svg+xml" data-haji-icon-file>
      @include('admin.partials.icon', ['name' => 'upload'])
    </label>
  </div>
  @if($isUpload)
    <label class="check haji-icon-clear-check">
      <input type="checkbox" name="{{ $namePrefix }}[clear_icon_upload]" value="1" @checked(old($fieldKey.'.clear_icon_upload'))>
      Hapus ikon upload, pakai Bootstrap
    </label>
  @endif
</div>

@if(filled($logo ?? null))
  <img class="master-logo-preview" src="{{ $logo }}" alt="">
@else
  <span class="master-logo-empty">No logo</span>
@endif
<label class="master-logo-upload" aria-label="Upload logo">
  <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
  @include('admin.partials.icon', ['name' => 'upload'])
</label>

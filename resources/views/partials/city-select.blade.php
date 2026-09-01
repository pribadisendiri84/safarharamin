@php
  $name = $name ?? 'kota';
  $selected = old($name, $selected ?? '');
  $cities = $cities ?? \App\Models\City::options($selected ?: null);
  $empty = $empty ?? 'Semua embarkasi';
  $required = $required ?? false;
  $placeholder = $placeholder ?? 'Cari kota…';
@endphp
<select name="{{ $name }}" class="js-searchable" data-placeholder="{{ $placeholder }}" @if($required) required @endif>
  @if($empty !== false)
    <option value="">{{ $empty }}</option>
  @endif
  @foreach($cities as $key => $label)
    <option value="{{ $key }}" @selected((string) $selected === (string) $key)>{{ $label }}</option>
  @endforeach
</select>

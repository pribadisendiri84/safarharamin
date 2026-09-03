@php
  $name = $name ?? 'hotel_makkah';
  $location = $location ?? \App\Models\Hotel::LOCATION_MAKKAH;
  $selected = old($name, $selected ?? '');
  $hotels = $hotels ?? \App\Models\Hotel::options($location, $selected ?: null);
  $empty = $empty ?? 'Pilih hotel';
  $required = $required ?? false;
  $placeholder = $placeholder ?? 'Cari hotel…';
  $inputId = $inputId ?? null;
@endphp
<select
  name="{{ $name }}"
  @if($inputId) id="{{ $inputId }}" @endif
  class="js-searchable"
  data-placeholder="{{ $placeholder }}"
  @if($required) required @endif
>
  @if($empty !== false)
    <option value="">{{ $empty }}</option>
  @endif
  @foreach($hotels as $value => $label)
    <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
  @endforeach
</select>

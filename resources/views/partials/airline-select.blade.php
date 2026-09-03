@php
  $name = $name ?? 'airline';
  $selected = old($name, $selected ?? '');
  $airlines = $airlines ?? \App\Models\Airline::options($selected ?: null);
  $empty = $empty ?? 'Pilih maskapai';
  $required = $required ?? false;
  $placeholder = $placeholder ?? 'Cari maskapai…';
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
  @foreach($airlines as $value => $label)
    <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
  @endforeach
</select>

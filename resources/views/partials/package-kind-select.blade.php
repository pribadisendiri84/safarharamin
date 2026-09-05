@php
  $name = $name ?? 'package_kind_id';
  $selected = old($name, $selected ?? '');
  $kinds = $kinds ?? \App\Models\PackageKind::options($selected !== '' && $selected !== null ? (int) $selected : null);
  $empty = $empty ?? 'Pilih tipe paket';
  $required = $required ?? true;
  $placeholder = $placeholder ?? 'Cari tipe paket…';
@endphp
<select
  name="{{ $name }}"
  class="js-searchable"
  data-placeholder="{{ $placeholder }}"
  @if($required) required @endif
>
  <option value="">{{ $empty }}</option>
  @foreach($kinds as $id => $label)
    <option value="{{ $id }}" @selected((string) $selected === (string) $id)>{{ $label }}</option>
  @endforeach
</select>

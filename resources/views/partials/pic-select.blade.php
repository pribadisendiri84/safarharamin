@php
  $name = $name ?? 'pic_id';
  $selected = old($name, $selected ?? '');
  $pics = $pics ?? \App\Models\Pic::options($selected !== '' && $selected !== null ? (int) $selected : null);
  $empty = $empty ?? 'Pilih PIC';
  $required = $required ?? true;
  $placeholder = $placeholder ?? 'Cari PIC…';
@endphp
<select
  name="{{ $name }}"
  class="js-searchable"
  data-placeholder="{{ $placeholder }}"
  @if($required) required @endif
>
  <option value="">{{ $empty }}</option>
  @foreach($pics as $id => $label)
    <option value="{{ $id }}" @selected((string) $selected === (string) $id)>{{ $label }}</option>
  @endforeach
</select>

@php
  $trashed = $trashed ?? request()->boolean('trashed');
  $trashedCount = $trashedCount ?? 0;
@endphp
<div class="tabs">
  <a class="{{ ! $trashed ? 'active' : '' }}" href="{{ request()->fullUrlWithoutQuery(['trashed', 'page']) }}">Aktif</a>
  <a class="{{ $trashed ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['trashed' => 1, 'page' => null]) }}">Terhapus ({{ $trashedCount }})</a>
</div>

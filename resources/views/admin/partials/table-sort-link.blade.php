@php
  $currentSort = request('sort', $defaultSort ?? 'updated_at');
  $currentDir = request('dir', $defaultDir ?? 'desc') === 'asc' ? 'asc' : 'desc';
  $isActive = $currentSort === $column;
  $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';
  $url = request()->fullUrlWithQuery([
      'sort' => $column,
      'dir' => $nextDir,
      'page' => null,
  ]);
  $indicator = $isActive
      ? ($currentDir === 'asc' ? 'A–Z' : 'Z–A')
      : '↕';
@endphp
<a href="{{ $url }}" @class(['table-sort-link', 'is-active' => $isActive, 'is-asc' => $isActive && $currentDir === 'asc', 'is-desc' => $isActive && $currentDir === 'desc'])>
  <span>{{ $label }}</span>
  <span class="table-sort-indicator" aria-hidden="true">{{ $indicator }}</span>
</a>

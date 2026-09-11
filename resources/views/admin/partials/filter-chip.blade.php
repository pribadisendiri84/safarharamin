<details class="filter-chip" @if(! empty($active)) open @endif>
  <summary @class(['is-active' => ! empty($active)])>
    <span class="filter-chip-label">{{ $label }}</span>
    @if(! empty($active) && filled($value ?? null))
      <span class="filter-chip-value">{{ $value }}</span>
    @endif
  </summary>
  <div class="filter-chip-menu">
    {{ $slot }}
  </div>
</details>
